<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Enums\GroupRole;
use App\Enums\GroupVisibility;
use App\Enums\JoinRequestStatus;
use App\Enums\MembershipStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\ReviewJoinRequest;
use App\Http\Requests\Web\StoreGroupInvitationRequest;
use App\Http\Requests\Web\StoreGroupRequest;
use App\Http\Requests\Web\TransferGroupOwnershipRequest;
use App\Http\Requests\Web\UpdateGroupRequest;
use App\Http\Requests\Web\UpdateMembershipRequest;
use App\Models\Group;
use App\Models\GroupInvitation;
use App\Models\GroupJoinRequest;
use App\Models\GroupMembership;
use App\Models\User;
use App\Services\Groups\GroupManagementService;
use App\Services\Permissions\GroupPermissionService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GroupController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly GroupManagementService $groupService,
        private readonly GroupPermissionService $permissionService,
    ) {}

    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();
        $search = trim((string) $request->query('q', ''));

        $myMemberships = $user->groupMemberships()
            ->with('group')
            ->whereHas('group')
            ->orderByDesc('joined_at')
            ->get();

        $publicGroups = Group::query()
            ->where('visibility', GroupVisibility::Public->value)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', '%'.$search.'%')
                        ->orWhere('tag', 'like', '%'.$search.'%')
                        ->orWhere('description', 'like', '%'.$search.'%');
                });
            })
            ->withCount(['memberships' => fn ($q) => $q->where('status', MembershipStatus::Active->value)])
            ->orderBy('name')
            ->get();

        return view('groups.index', [
            'myMemberships' => $myMemberships,
            'publicGroups' => $publicGroups,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Group::class);

        return view('groups.create');
    }

    public function store(StoreGroupRequest $request): RedirectResponse
    {
        $this->authorize('create', Group::class);

        /** @var User $user */
        $user = $request->user();

        $group = $this->groupService->createGroup($user, $request->validated());

        return redirect()
            ->route('groups.show', $group)
            ->with('status', 'group-created');
    }

    public function show(Request $request, Group $group): View
    {
        $this->authorize('view', $group);

        /** @var User $user */
        $user = $request->user();

        $activeRole = $this->permissionService->roleFor($user, $group);
        $canManageMembers = $this->permissionService->canManageMembers($user, $group);
        $canManageSettings = $this->permissionService->canManageSettings($user, $group);

        $memberships = $group->memberships()
            ->with('user')
            ->when(! $canManageMembers, fn ($q) => $q->where('status', MembershipStatus::Active->value))
            ->orderBy('role')
            ->get();

        $invitations = $canManageMembers
            ? $group->invitations()->orderByDesc('id')->get()
            : collect();

        $pendingJoinRequestsCount = $canManageMembers
            ? $group->joinRequests()->where('status', JoinRequestStatus::Pending->value)->count()
            : 0;

        $mods = $group->mods()
            ->with(['game', 'versions.source', 'versions.dependencies.relatedMod'])
            ->orderBy('name')
            ->get();

        $convoyProfiles = $group->convoyProfiles()
            ->with(['game', 'profileMods.modVersion.mod', 'profileMods.modVersion.source', 'syncSessions.user', 'syncSessions.device', 'syncApprovals.user'])
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        return view('groups.show', [
            'group' => $group,
            'activeRole' => $activeRole,
            'canManageMembers' => $canManageMembers,
            'canManageSettings' => $canManageSettings,
            'memberships' => $memberships,
            'invitations' => $invitations,
            'pendingJoinRequestsCount' => $pendingJoinRequestsCount,
            'mods' => $mods,
            'convoyProfiles' => $convoyProfiles,
        ]);
    }

    public function members(Request $request, Group $group): View
    {
        $this->authorize('view', $group);

        /** @var User $user */
        $user = $request->user();
        $activeRole = $this->permissionService->roleFor($user, $group);
        $canManageMembers = $this->permissionService->canManageMembers($user, $group);
        $canAssignRoles = $this->permissionService->canAssignRoles($user, $group);
        $canManageSettings = $this->permissionService->canManageSettings($user, $group);

        $memberships = $group->memberships()
            ->with('user')
            ->when(! $canManageMembers, fn ($q) => $q->where('status', MembershipStatus::Active->value))
            ->orderBy('role')
            ->get();

        return view('groups.members', [
            'group' => $group,
            'activeRole' => $activeRole,
            'canManageMembers' => $canManageMembers,
            'canAssignRoles' => $canAssignRoles,
            'canManageSettings' => $canManageSettings,
            'memberships' => $memberships,
        ]);
    }

    public function joinRequests(Request $request, Group $group): View
    {
        $this->authorize('manageMembers', $group);

        /** @var User $user */
        $user = $request->user();

        $joinRequests = $group->joinRequests()
            ->with(['user', 'reviewer'])
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderByDesc('id')
            ->get();

        return view('groups.join-requests', [
            'group' => $group,
            'activeRole' => $this->permissionService->roleFor($user, $group),
            'canManageMembers' => true,
            'canManageSettings' => $this->permissionService->canManageSettings($user, $group),
            'joinRequests' => $joinRequests,
        ]);
    }

    public function invitations(Request $request, Group $group): View
    {
        $this->authorize('manageMembers', $group);

        /** @var User $user */
        $user = $request->user();

        $invitations = $group->invitations()
            ->with('inviter')
            ->orderByDesc('id')
            ->get();

        return view('groups.invitations', [
            'group' => $group,
            'activeRole' => $this->permissionService->roleFor($user, $group),
            'canManageMembers' => true,
            'canManageSettings' => $this->permissionService->canManageSettings($user, $group),
            'invitations' => $invitations,
        ]);
    }

    public function settings(Request $request, Group $group): View
    {
        $this->authorize('update', $group);

        /** @var User $user */
        $user = $request->user();

        $activeMembers = $group->memberships()
            ->with('user')
            ->where('status', MembershipStatus::Active->value)
            ->where('user_id', '!=', $group->owner_id)
            ->get();

        return view('groups.settings', [
            'group' => $group,
            'activeRole' => $this->permissionService->roleFor($user, $group),
            'canManageMembers' => $this->permissionService->canManageMembers($user, $group),
            'canManageSettings' => true,
            'canTransferOwnership' => $this->permissionService->canTransferOwnership($user, $group),
            'activeMembers' => $activeMembers,
        ]);
    }

    public function update(UpdateGroupRequest $request, Group $group): RedirectResponse
    {
        $this->authorize('update', $group);

        /** @var User $user */
        $user = $request->user();

        $this->groupService->updateGroup($group, $user, $request->validated());

        return redirect()
            ->route('groups.show', $group)
            ->with('status', 'group-updated');
    }

    public function destroy(Request $request, Group $group): RedirectResponse
    {
        $this->authorize('delete', $group);

        /** @var User $user */
        $user = $request->user();

        $this->groupService->deleteGroup($group, $user);

        return redirect()
            ->route('groups.index')
            ->with('status', 'group-deleted');
    }

    public function transferOwnership(TransferGroupOwnershipRequest $request, Group $group): RedirectResponse
    {
        $this->authorize('transferOwnership', $group);

        /** @var User $user */
        $user = $request->user();
        /** @var User $newOwner */
        $newOwner = User::query()->findOrFail((int) $request->validated('new_owner_id'));

        $this->groupService->transferOwnership($group, $user, $newOwner);

        return redirect()
            ->route('groups.show', $group)
            ->with('status', 'ownership-transferred');
    }

    public function join(Request $request, Group $group): RedirectResponse
    {
        $validated = $request->validate([
            'message' => ['nullable', 'string', 'max:500'],
        ]);

        /** @var User $user */
        $user = $request->user();

        $membership = $this->groupService->requestJoinPublicGroup(
            group: $group,
            user: $user,
            message: $validated['message'] ?? null,
        );

        return redirect()
            ->route('groups.show', $group)
            ->with('status', $membership->status === MembershipStatus::Active ? 'group-joined' : 'group-join-requested');
    }

    public function reviewJoinRequest(
        ReviewJoinRequest $request,
        Group $group,
        GroupJoinRequest $joinRequest,
    ): RedirectResponse {
        $this->authorize('manageMembers', $group);

        /** @var User $user */
        $user = $request->user();
        $validated = $request->validated();

        $this->groupService->reviewJoinRequest(
            joinRequest: $joinRequest,
            reviewer: $user,
            decision: JoinRequestStatus::from((string) $validated['status']),
            reviewNotes: $validated['review_notes'] ?? null,
        );

        return redirect()
            ->route('groups.join-requests', $group)
            ->with('status', 'join-request-reviewed');
    }

    public function updateMembership(
        UpdateMembershipRequest $request,
        Group $group,
        GroupMembership $membership,
    ): RedirectResponse {
        $this->authorize('manageMembers', $group);

        /** @var User $user */
        $user = $request->user();
        $validated = $request->validated();

        $newRole = isset($validated['role']) ? GroupRole::from((string) $validated['role']) : null;
        $newStatus = isset($validated['status']) ? MembershipStatus::from((string) $validated['status']) : null;

        $this->groupService->updateMembership($group, $membership, $user, $newRole, $newStatus);

        return redirect()
            ->route('groups.show', $group)
            ->with('status', 'membership-updated');
    }

    public function destroyMembership(
        Request $request,
        Group $group,
        GroupMembership $membership,
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();

        if ($membership->user_id !== $user->id) {
            $this->authorize('manageMembers', $group);
        }

        $this->groupService->removeMember($group, $membership, $user);

        return redirect()
            ->route($membership->user_id === $user->id ? 'groups.index' : 'groups.members', $membership->user_id === $user->id ? [] : $group)
            ->with('status', 'member-removed');
    }

    public function storeInvitation(StoreGroupInvitationRequest $request, Group $group): RedirectResponse
    {
        $this->authorize('manageMembers', $group);

        /** @var User $user */
        $user = $request->user();
        $validated = $request->validated();

        $invitation = $this->groupService->createInvitation(
            group: $group,
            inviter: $user,
            role: GroupRole::from((string) $validated['role']),
            email: $validated['email'] ?? null,
            maxUses: isset($validated['max_uses']) ? (int) $validated['max_uses'] : 1,
            expiresInDays: isset($validated['expires_in_days']) ? (int) $validated['expires_in_days'] : 7,
        );

        return redirect()
            ->route('groups.show', $group)
            ->with('status', 'invitation-created')
            ->with('invitation_token', $invitation->token);
    }

    public function destroyInvitation(
        Request $request,
        Group $group,
        GroupInvitation $invitation,
    ): RedirectResponse {
        $this->authorize('manageMembers', $group);

        /** @var User $user */
        $user = $request->user();

        $this->groupService->revokeInvitation($group, $invitation, $user);

        return redirect()
            ->route('groups.invitations', $group)
            ->with('status', 'invitation-revoked');
    }

    public function acceptInvitation(Request $request, string $token): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $membership = $this->groupService->acceptInvitation($token, $user);

        return redirect()
            ->route('groups.show', $membership->group)
            ->with('status', 'invitation-accepted');
    }
}
