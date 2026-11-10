<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\Enums\JoinRequestStatus;
use App\Enums\MembershipStatus;
use App\Enums\ModVersionStatus;
use App\Enums\SyncStatus;
use App\Models\AuditLog;
use App\Models\ConvoyProfile;
use App\Models\Device;
use App\Models\Game;
use App\Models\GroupInvitation;
use App\Models\GroupJoinRequest;
use App\Models\GroupMembership;
use App\Models\Mod;
use App\Models\ModVersion;
use App\Models\SyncApproval;
use App\Models\SyncSession;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Builds the read-only data set behind the user dashboard overview.
 *
 * Every query is scoped to the signed-in user's own ACTIVE group memberships.
 * Platform-admin status intentionally grants no extra visibility here.
 */
class DashboardOverviewService
{
    /** Audit events shown when the signed-in user performed them. */
    private const OWN_EVENTS = [
        'group.created' => 'You created a group',
        'membership.joined' => 'You joined a group',
        'membership.requested' => 'You requested to join a group',
        'invitation.accepted' => 'You accepted an invitation',
        'device.registered' => 'You registered a desktop device',
        'sync_session.reported' => 'Your desktop client reported a sync',
    ];

    /** Audit events shown for any actor, but only inside groups the user actively belongs to. */
    private const GROUP_EVENTS = [
        'mod.created' => 'A mod was added',
        'mod_version.published_s3' => 'A mod version was uploaded',
        'mod_version.published_steam_workshop' => 'A Steam Workshop mod version was added',
        'convoy_profile.created' => 'A convoy profile was created',
        'convoy_profile.manifest_updated' => 'A convoy profile was updated',
    ];

    /** First-person labels for GROUP_EVENTS when the signed-in user is the actor. */
    private const OWN_GROUP_EVENTS = [
        'mod.created' => 'You added a mod',
        'mod_version.published_s3' => 'You uploaded a mod version',
        'mod_version.published_steam_workshop' => 'You added a Steam Workshop mod version',
        'convoy_profile.created' => 'You created a convoy profile',
        'convoy_profile.manifest_updated' => 'You updated a convoy profile',
    ];

    /**
     * @return array<string, mixed>
     */
    public function forUser(User $user): array
    {
        $memberships = GroupMembership::query()
            ->where('user_id', $user->id)
            ->where('status', MembershipStatus::Active)
            ->whereHas('group')
            ->with('group')
            ->get();

        // One grouped query for member counts (avoids a COUNT per group).
        $memberCounts = GroupMembership::query()
            ->whereIn('group_id', $memberships->pluck('group_id'))
            ->where('status', MembershipStatus::Active)
            ->selectRaw('group_id, count(*) as aggregate')
            ->groupBy('group_id')
            ->pluck('aggregate', 'group_id');

        $memberships = $memberships
            ->each(fn (GroupMembership $m) => $m->group->setAttribute('active_members_count', (int) ($memberCounts[$m->group_id] ?? 0)))
            ->sortBy(fn (GroupMembership $m) => mb_strtolower($m->group->name))
            ->values();

        $groupIds = $memberships->pluck('group_id')->all();
        $groupNames = $memberships->mapWithKeys(fn (GroupMembership $m) => [$m->group_id => $m->group->name]);

        $approverGroupIds = $memberships
            ->filter(fn (GroupMembership $m) => $m->role->canApproveSync())
            ->pluck('group_id')
            ->all();

        $devices = Device::query()
            ->where('user_id', $user->id)
            ->orderByDesc('last_seen_at')
            ->get();
        $activeDevices = $devices->whereNull('revoked_at')->values();

        $profiles = $groupIds === [] ? collect() : ConvoyProfile::query()
            ->whereIn('group_id', $groupIds)
            ->where('is_active', true)
            ->with(['group', 'game'])
            ->orderBy('group_id')
            ->orderBy('name')
            ->get();

        $mySessions = SyncSession::query()
            ->where('user_id', $user->id)
            ->whereIn('convoy_profile_id', $profiles->pluck('id'))
            ->with(['device', 'convoyProfile.group'])
            ->latest('updated_at')
            ->get();

        // Latest session per profile, used for the per-profile status badge.
        $latestByProfile = $mySessions->unique('convoy_profile_id')->keyBy('convoy_profile_id');
        $profiles->each(fn (ConvoyProfile $p) => $p->setRelation('mySession', $latestByProfile->get($p->id)));

        $myPendingApprovals = SyncApproval::query()
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->whereIn('convoy_profile_id', $profiles->pluck('id'))
            ->count();

        $invitations = $this->pendingInvitations($user, $groupIds);
        $approvals = $this->pendingApprovals($approverGroupIds);

        return [
            'user' => $user,
            'memberships' => $memberships,
            'groupGames' => $this->gamesByGroup($groupIds, $profiles),
            'profiles' => $profiles,
            'recentSessions' => $mySessions->take(5)->values(),
            'devices' => $activeDevices->take(4)->values(),
            'recentMods' => $this->recentModVersions($groupIds),
            'invitations' => $invitations,
            'joinRequests' => $this->pendingJoinRequests($user),
            'approvals' => $approvals,
            'activity' => $this->recentActivity($user, $groupIds, $groupNames),
            'attentionCount' => $invitations->count() + $approvals->count(),
            'kpis' => [
                'groups' => $memberships->count(),
                'devices' => $activeDevices->count(),
                'profiles' => $profiles->count(),
                'mods' => $this->availableModCount($groupIds),
                'last_sync' => $mySessions->first(fn (SyncSession $s) => $s->synced_at !== null)?->synced_at,
                'out_of_sync' => $latestByProfile->filter(
                    fn (SyncSession $s) => in_array($s->status, [SyncStatus::OutOfSync, SyncStatus::Failed], true)
                )->count(),
            ],
            'sync' => [
                'has_devices' => $activeDevices->isNotEmpty(),
                'has_sessions' => $mySessions->isNotEmpty(),
                'latest' => $mySessions->first(),
                'failed' => $mySessions->where('status', SyncStatus::Failed)->count(),
                'my_pending_approvals' => $myPendingApprovals,
                'awaiting_my_review' => $approvals->count(),
            ],
            'checklist' => [
                'joined_group' => $memberships->isNotEmpty(),
                'registered_device' => $activeDevices->isNotEmpty(),
                'synced' => $mySessions->contains(fn (SyncSession $s) => $s->status === SyncStatus::InSync),
            ],
        ];
    }

    /**
     * Games each group is known to support, derived from its mods and convoy profiles.
     *
     * @param  list<int>  $groupIds
     * @param  Collection<int, ConvoyProfile>  $profiles
     * @return Collection<int, Collection<int, string>> group_id => game names
     */
    private function gamesByGroup(array $groupIds, Collection $profiles): Collection
    {
        if ($groupIds === []) {
            return collect();
        }

        $pairs = Mod::query()
            ->whereIn('group_id', $groupIds)
            ->where('is_active', true)
            ->select('group_id', 'game_id')
            ->distinct()
            ->get()
            ->toBase()
            ->map(fn (Mod $m) => [$m->group_id, $m->game_id])
            ->merge($profiles->map(fn (ConvoyProfile $p) => [$p->group_id, $p->game_id]));

        $games = Game::query()->whereIn('id', $pairs->pluck(1)->unique())->pluck('name', 'id');

        return $pairs->groupBy(0)->map(
            fn (Collection $rows) => $rows->pluck(1)->unique()->map(fn ($id) => $games[$id] ?? null)->filter()->sort()->values()
        );
    }

    /**
     * @param  list<int>  $groupIds
     */
    private function availableModCount(array $groupIds): int
    {
        if ($groupIds === []) {
            return 0;
        }

        return Mod::query()
            ->whereIn('group_id', $groupIds)
            ->where('is_active', true)
            ->whereHas('versions', fn ($q) => $q->where('status', ModVersionStatus::Ready))
            ->count();
    }

    /**
     * Real audit-log events only: the user's own actions plus a small whitelist of
     * group-visible events from groups the user actively belongs to.
     *
     * @param  list<int>  $groupIds
     * @param  Collection<int, string>  $groupNames
     * @return Collection<int, array{label: string, group: ?string, at: Carbon}>
     */
    private function recentActivity(User $user, array $groupIds, Collection $groupNames): Collection
    {
        $logs = AuditLog::query()
            ->where(function ($q) use ($user, $groupIds): void {
                $q->where(fn ($own) => $own->where('user_id', $user->id)->whereIn('event', array_keys(self::OWN_EVENTS)));

                if ($groupIds !== []) {
                    $q->orWhere(fn ($grp) => $grp->whereIn('group_id', $groupIds)->whereIn('event', array_keys(self::GROUP_EVENTS)));
                }
            })
            ->select('id', 'group_id', 'user_id', 'event', 'created_at')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        return $logs->map(function (AuditLog $log) use ($user, $groupNames): array {
            $isActor = $log->user_id === $user->id;
            $label = match (true) {
                $isActor && isset(self::OWN_EVENTS[$log->event]) => self::OWN_EVENTS[$log->event],
                $isActor && isset(self::OWN_GROUP_EVENTS[$log->event]) => self::OWN_GROUP_EVENTS[$log->event],
                default => self::GROUP_EVENTS[$log->event],
            };

            return [
                'label' => $label,
                'group' => $log->group_id ? ($groupNames[$log->group_id] ?? null) : null,
                'at' => $log->created_at,
            ];
        });
    }

    /**
     * @param  list<int>  $groupIds
     * @return Collection<int, ModVersion>
     */
    private function recentModVersions(array $groupIds): Collection
    {
        if ($groupIds === []) {
            return collect();
        }

        return ModVersion::query()
            ->where('status', ModVersionStatus::Ready)
            ->whereHas('mod', fn ($q) => $q->whereIn('group_id', $groupIds)->where('is_active', true))
            ->with('mod.group')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(5)
            ->get();
    }

    /**
     * Pending, still-valid invitations addressed to the user's own e-mail address.
     * Excludes groups where the user is already an active member or has been banned.
     *
     * @param  list<int>  $memberGroupIds
     * @return Collection<int, GroupInvitation>
     */
    private function pendingInvitations(User $user, array $memberGroupIds): Collection
    {
        $bannedGroupIds = GroupMembership::query()
            ->where('user_id', $user->id)
            ->where('status', MembershipStatus::Banned)
            ->pluck('group_id')
            ->all();

        $excludedGroupIds = array_values(array_unique(array_merge($memberGroupIds, $bannedGroupIds)));

        return GroupInvitation::query()
            ->whereRaw('LOWER(email) = ?', [strtolower($user->email)])
            ->whereNotIn('group_id', $excludedGroupIds)
            ->whereHas('group')
            ->with('group')
            ->latest()
            ->get()
            ->filter(fn (GroupInvitation $i) => $i->isValid())
            ->values();
    }

    /**
     * @return Collection<int, GroupJoinRequest>
     */
    private function pendingJoinRequests(User $user): Collection
    {
        return GroupJoinRequest::query()
            ->where('user_id', $user->id)
            ->where('status', JoinRequestStatus::Pending)
            ->whereHas('group')
            ->with('group')
            ->latest()
            ->get();
    }

    /**
     * Sync approvals the user may review (only groups where their role allows it).
     *
     * @param  list<int>  $approverGroupIds
     * @return Collection<int, SyncApproval>
     */
    private function pendingApprovals(array $approverGroupIds): Collection
    {
        if ($approverGroupIds === []) {
            return collect();
        }

        return SyncApproval::query()
            ->where('status', 'pending')
            ->whereHas('convoyProfile', fn ($q) => $q->whereIn('group_id', $approverGroupIds))
            ->with(['convoyProfile.group', 'user'])
            ->latest()
            ->limit(10)
            ->get();
    }
}
