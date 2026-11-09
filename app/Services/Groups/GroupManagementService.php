<?php

declare(strict_types=1);

namespace App\Services\Groups;

use App\Enums\GroupRole;
use App\Enums\GroupVisibility;
use App\Enums\JoinRequestStatus;
use App\Enums\MembershipStatus;
use App\Enums\SyncPolicyMode;
use App\Models\Group;
use App\Models\GroupInvitation;
use App\Models\GroupJoinRequest;
use App\Models\GroupMembership;
use App\Models\User;
use App\Services\Audit\AuditLoggerService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GroupManagementService
{
    public function __construct(
        private readonly AuditLoggerService $auditLogger,
    ) {}

    /**
     * Create a new group/VTC and assign the creator as active Group Owner.
     *
     * @param  array{
     *     name: string,
     *     slug?: string|null,
     *     tag?: string|null,
     *     description?: string|null,
     *     visibility: GroupVisibility|string,
     *     sync_policy: SyncPolicyMode|string,
     *     require_join_approval?: bool
     * }  $data
     */
    public function createGroup(User $owner, array $data): Group
    {
        return DB::transaction(function () use ($owner, $data): Group {
            $slug = ! empty($data['slug'])
                ? Str::slug((string) $data['slug'])
                : Str::slug((string) $data['name']);

            if (Group::withTrashed()->where('slug', $slug)->exists()) {
                $slug .= '-'.strtolower(Str::random(4));
            }

            $group = Group::query()->create([
                'owner_id' => $owner->id,
                'name' => $data['name'],
                'slug' => $slug,
                'tag' => $data['tag'] ?? null,
                'description' => $data['description'] ?? null,
                'visibility' => $data['visibility'] instanceof GroupVisibility
                    ? $data['visibility']
                    : GroupVisibility::from((string) $data['visibility']),
                'sync_policy' => $data['sync_policy'] instanceof SyncPolicyMode
                    ? $data['sync_policy']
                    : SyncPolicyMode::from((string) $data['sync_policy']),
                'require_join_approval' => (bool) ($data['require_join_approval'] ?? true),
                'require_mod_approval' => (bool) ($data['require_mod_approval'] ?? true),
                'logo_path' => $data['logo_path'] ?? null,
                'banner_path' => $data['banner_path'] ?? null,
            ]);

            GroupMembership::query()->create([
                'group_id' => $group->id,
                'user_id' => $owner->id,
                'role' => GroupRole::Owner,
                'status' => MembershipStatus::Active,
                'joined_at' => now(),
            ]);

            $this->auditLogger->log(
                event: 'group.created',
                auditable: $group,
                actor: $owner,
                groupId: $group->id,
                newValues: [
                    'name' => $group->name,
                    'slug' => $group->slug,
                    'visibility' => $group->visibility->value,
                    'sync_policy' => $group->sync_policy->value,
                ],
            );

            return $group;
        });
    }

    /**
     * Update an existing group's settings.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateGroup(Group $group, User $actor, array $data): Group
    {
        return DB::transaction(function () use ($group, $actor, $data): Group {
            $oldValues = [
                'name' => $group->name,
                'tag' => $group->tag,
                'visibility' => $group->visibility->value,
                'sync_policy' => $group->sync_policy->value,
                'require_join_approval' => $group->require_join_approval,
            ];

            $group->update([
                'name' => $data['name'] ?? $group->name,
                'tag' => array_key_exists('tag', $data) ? $data['tag'] : $group->tag,
                'description' => array_key_exists('description', $data) ? $data['description'] : $group->description,
                'visibility' => isset($data['visibility'])
                    ? ($data['visibility'] instanceof GroupVisibility ? $data['visibility'] : GroupVisibility::from((string) $data['visibility']))
                    : $group->visibility,
                'sync_policy' => isset($data['sync_policy'])
                    ? ($data['sync_policy'] instanceof SyncPolicyMode ? $data['sync_policy'] : SyncPolicyMode::from((string) $data['sync_policy']))
                    : $group->sync_policy,
                'require_join_approval' => isset($data['require_join_approval'])
                    ? (bool) $data['require_join_approval']
                    : $group->require_join_approval,
                'require_mod_approval' => isset($data['require_mod_approval'])
                    ? (bool) $data['require_mod_approval']
                    : $group->require_mod_approval,
                'logo_path' => array_key_exists('logo_path', $data) ? $data['logo_path'] : $group->logo_path,
                'banner_path' => array_key_exists('banner_path', $data) ? $data['banner_path'] : $group->banner_path,
            ]);

            $this->auditLogger->log(
                event: 'group.updated',
                auditable: $group,
                actor: $actor,
                groupId: $group->id,
                oldValues: $oldValues,
                newValues: [
                    'name' => $group->name,
                    'tag' => $group->tag,
                    'visibility' => $group->visibility->value,
                    'sync_policy' => $group->sync_policy->value,
                    'require_join_approval' => $group->require_join_approval,
                    'require_mod_approval' => $group->require_mod_approval,
                ],
            );

            return $group;
        });
    }

    /**
     * Soft-delete (archive) a group.
     */
    public function deleteGroup(Group $group, User $actor): void
    {
        DB::transaction(function () use ($group, $actor): void {
            $this->auditLogger->log(
                event: 'group.deleted',
                auditable: $group,
                actor: $actor,
                groupId: $group->id,
                oldValues: [
                    'name' => $group->name,
                    'slug' => $group->slug,
                ],
            );

            $group->delete();
        });
    }

    /**
     * Transfer group ownership to another active member of the group.
     */
    public function transferOwnership(Group $group, User $currentOwner, User $newOwner): Group
    {
        if ($group->owner_id === $newOwner->id) {
            return $group;
        }

        $targetMembership = $group->membershipFor($newOwner);

        if ($targetMembership === null || $targetMembership->status !== MembershipStatus::Active) {
            throw ValidationException::withMessages([
                'new_owner_id' => ['The new owner must be an active member of this group.'],
            ]);
        }

        return DB::transaction(function () use ($group, $currentOwner, $newOwner, $targetMembership): Group {
            $oldOwnerId = $group->owner_id;

            // Demote previous owner to Administrator
            GroupMembership::query()->updateOrCreate(
                [
                    'group_id' => $group->id,
                    'user_id' => $oldOwnerId,
                ],
                [
                    'role' => GroupRole::Admin,
                    'status' => MembershipStatus::Active,
                ]
            );

            // Promote new owner to Group Owner
            $targetMembership->update([
                'role' => GroupRole::Owner,
                'status' => MembershipStatus::Active,
            ]);

            $group->update([
                'owner_id' => $newOwner->id,
            ]);

            $this->auditLogger->log(
                event: 'group.ownership_transferred',
                auditable: $group,
                actor: $currentOwner,
                groupId: $group->id,
                oldValues: ['owner_id' => $oldOwnerId],
                newValues: ['owner_id' => $newOwner->id],
            );

            return $group->fresh() ?? $group;
        });
    }

    /**
     * Request to join a public group (or immediately join if require_join_approval is false).
     */
    public function requestJoinPublicGroup(Group $group, User $user, ?string $message = null): GroupMembership
    {
        if ($group->isPrivate()) {
            throw ValidationException::withMessages([
                'group' => ['Private groups can only be joined via invitation.'],
            ]);
        }

        return DB::transaction(function () use ($group, $user, $message): GroupMembership {
            $existing = $group->membershipFor($user);
            if ($existing !== null) {
                if (in_array($existing->status, [MembershipStatus::Banned, MembershipStatus::Suspended], true)) {
                    throw ValidationException::withMessages([
                        'group' => ['You are not permitted to join this group.'],
                    ]);
                }

                return $existing;
            }

            $status = $group->require_join_approval
                ? MembershipStatus::Pending
                : MembershipStatus::Active;

            if ($group->require_join_approval) {
                GroupJoinRequest::query()->create([
                    'group_id' => $group->id,
                    'user_id' => $user->id,
                    'message' => $message,
                    'status' => JoinRequestStatus::Pending,
                ]);
            }

            $membership = GroupMembership::query()->create([
                'group_id' => $group->id,
                'user_id' => $user->id,
                'role' => GroupRole::Member,
                'status' => $status,
                'joined_at' => $status === MembershipStatus::Active ? now() : null,
            ]);

            $this->auditLogger->log(
                event: $status === MembershipStatus::Active ? 'membership.joined' : 'membership.requested',
                auditable: $membership,
                actor: $user,
                groupId: $group->id,
                newValues: [
                    'user_id' => $user->id,
                    'role' => $membership->role->value,
                    'status' => $membership->status->value,
                ],
            );

            return $membership;
        });
    }

    /**
     * Review an application in group_join_requests.
     */
    public function reviewJoinRequest(
        GroupJoinRequest $joinRequest,
        User $reviewer,
        JoinRequestStatus $decision,
        ?string $reviewNotes = null,
    ): GroupJoinRequest {
        if (! $joinRequest->isPending()) {
            throw ValidationException::withMessages([
                'status' => ['This join request has already been reviewed.'],
            ]);
        }

        return DB::transaction(function () use ($joinRequest, $reviewer, $decision, $reviewNotes): GroupJoinRequest {
            $joinRequest->update([
                'status' => $decision,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'review_notes' => $reviewNotes,
            ]);

            if ($decision === JoinRequestStatus::Approved) {
                GroupMembership::query()->updateOrCreate(
                    [
                        'group_id' => $joinRequest->group_id,
                        'user_id' => $joinRequest->user_id,
                    ],
                    [
                        'role' => GroupRole::Member,
                        'status' => MembershipStatus::Active,
                        'approved_by' => $reviewer->id,
                        'joined_at' => now(),
                    ]
                );
            } elseif ($decision === JoinRequestStatus::Rejected) {
                GroupMembership::query()
                    ->where('group_id', $joinRequest->group_id)
                    ->where('user_id', $joinRequest->user_id)
                    ->where('status', MembershipStatus::Pending->value)
                    ->delete();
            }

            $this->auditLogger->log(
                event: 'join_request.reviewed',
                auditable: $joinRequest,
                actor: $reviewer,
                groupId: $joinRequest->group_id,
                newValues: [
                    'user_id' => $joinRequest->user_id,
                    'status' => $decision->value,
                    'review_notes' => $reviewNotes,
                ],
            );

            return $joinRequest;
        });
    }

    /**
     * Update a member's role or status in a group.
     */
    public function updateMembership(
        Group $group,
        GroupMembership $membership,
        User $actor,
        ?GroupRole $newRole = null,
        ?MembershipStatus $newStatus = null,
    ): GroupMembership {
        if ($membership->group_id !== $group->id) {
            throw ValidationException::withMessages([
                'membership' => ['Membership does not belong to this group.'],
            ]);
        }

        if ($membership->user_id === $group->owner_id) {
            throw ValidationException::withMessages([
                'role' => ['The group owner cannot be modified or suspended without transferring ownership.'],
            ]);
        }

        $actorRole = $actor->is_platform_admin ? GroupRole::Owner : $group->activeRoleFor($actor);

        if ($actorRole === null || $actorRole->rank() <= $membership->role->rank()) {
            throw ValidationException::withMessages([
                'membership' => ['You cannot modify a member with an equal or higher role than your own.'],
            ]);
        }

        if ($newRole !== null && $newRole !== $membership->role) {
            if (! $actorRole->canAssignRoles()) {
                throw ValidationException::withMessages([
                    'role' => ['Your role does not permit changing member roles.'],
                ]);
            }

            if ($newRole === GroupRole::Owner) {
                throw ValidationException::withMessages([
                    'role' => ['Use the transfer ownership action to assign a new Group Owner.'],
                ]);
            }

            if ($newRole->rank() >= $actorRole->rank() && $actorRole !== GroupRole::Owner) {
                throw ValidationException::withMessages([
                    'role' => ['You cannot promote a member to a role equal to or higher than your own.'],
                ]);
            }
        }

        return DB::transaction(function () use ($group, $membership, $actor, $newRole, $newStatus): GroupMembership {
            $oldValues = [
                'role' => $membership->role->value,
                'status' => $membership->status->value,
            ];

            $updates = [];
            if ($newRole !== null) {
                $updates['role'] = $newRole;
            }

            if ($newStatus !== null) {
                $updates['status'] = $newStatus;
                if ($newStatus === MembershipStatus::Active && $membership->joined_at === null) {
                    $updates['joined_at'] = now();
                    $updates['approved_by'] = $actor->id;
                }
            }

            $membership->update($updates);

            if ($newStatus === MembershipStatus::Active) {
                GroupJoinRequest::query()
                    ->where('group_id', $group->id)
                    ->where('user_id', $membership->user_id)
                    ->where('status', JoinRequestStatus::Pending->value)
                    ->update([
                        'status' => JoinRequestStatus::Approved->value,
                        'reviewed_by' => $actor->id,
                        'reviewed_at' => now(),
                    ]);
            }

            $this->auditLogger->log(
                event: 'membership.updated',
                auditable: $membership,
                actor: $actor,
                groupId: $group->id,
                oldValues: $oldValues,
                newValues: [
                    'role' => $membership->role->value,
                    'status' => $membership->status->value,
                ],
            );

            return $membership;
        });
    }

    /**
     * Remove a member from a group (or allow a non-owner member to leave).
     */
    public function removeMember(Group $group, GroupMembership $membership, User $actor): void
    {
        if ($membership->group_id !== $group->id) {
            throw ValidationException::withMessages([
                'membership' => ['Membership does not belong to this group.'],
            ]);
        }

        if ($membership->user_id === $group->owner_id) {
            throw ValidationException::withMessages([
                'membership' => ['The group owner cannot be removed without transferring ownership first.'],
            ]);
        }

        $isSelfRemoval = $membership->user_id === $actor->id;
        if (! $isSelfRemoval) {
            $actorRole = $actor->is_platform_admin ? GroupRole::Owner : $group->activeRoleFor($actor);
            if ($actorRole === null || ! $actorRole->canManageMembers() || $actorRole->rank() <= $membership->role->rank()) {
                throw ValidationException::withMessages([
                    'membership' => ['You do not have permission to remove this member.'],
                ]);
            }
        }

        DB::transaction(function () use ($group, $membership, $actor): void {
            $this->auditLogger->log(
                event: 'membership.removed',
                auditable: $membership,
                actor: $actor,
                groupId: $group->id,
                oldValues: [
                    'user_id' => $membership->user_id,
                    'role' => $membership->role->value,
                    'status' => $membership->status->value,
                ],
            );

            $membership->delete();
        });
    }

    /**
     * Revoke an active group invitation token.
     */
    public function revokeInvitation(Group $group, GroupInvitation $invitation, User $actor): GroupInvitation
    {
        if ($invitation->group_id !== $group->id) {
            throw ValidationException::withMessages([
                'invitation' => ['Invitation does not belong to this group.'],
            ]);
        }

        return DB::transaction(function () use ($group, $invitation, $actor): GroupInvitation {
            $invitation->update([
                'revoked_at' => now(),
            ]);

            $this->auditLogger->log(
                event: 'invitation.revoked',
                auditable: $invitation,
                actor: $actor,
                groupId: $group->id,
                newValues: [
                    'revoked_at' => $invitation->revoked_at?->toIso8601ZuluString(),
                ],
            );

            return $invitation;
        });
    }

    /**
     * Issue an invitation token for a group.
     */
    public function createInvitation(
        Group $group,
        User $inviter,
        GroupRole $role = GroupRole::Member,
        ?string $email = null,
        ?int $maxUses = 1,
        int $expiresInDays = 7,
    ): GroupInvitation {
        if ($role === GroupRole::Owner) {
            throw ValidationException::withMessages([
                'role' => ['Invitations cannot grant the Group Owner role.'],
            ]);
        }

        $inviterRole = $inviter->is_platform_admin ? GroupRole::Owner : $group->activeRoleFor($inviter);
        if ($inviterRole === null || ! $inviterRole->canManageMembers()) {
            throw ValidationException::withMessages([
                'role' => ['You do not have permission to create invitations for this group.'],
            ]);
        }

        if ($role !== GroupRole::Member && (! $inviterRole->canAssignRoles() || ($inviterRole !== GroupRole::Owner && $role->rank() >= $inviterRole->rank()))) {
            throw ValidationException::withMessages([
                'role' => ['You cannot issue an invitation for a role equal to or higher than your own, or assign elevated roles without administrator permission.'],
            ]);
        }

        return DB::transaction(function () use ($group, $inviter, $role, $email, $maxUses, $expiresInDays): GroupInvitation {
            $invitation = GroupInvitation::query()->create([
                'group_id' => $group->id,
                'invited_by' => $inviter->id,
                'email' => $email ? strtolower($email) : null,
                'token' => bin2hex(random_bytes(32)),
                'role' => $role,
                'max_uses' => $maxUses,
                'uses_count' => 0,
                'expires_at' => now()->addDays($expiresInDays),
            ]);

            $this->auditLogger->log(
                event: 'invitation.created',
                auditable: $invitation,
                actor: $inviter,
                groupId: $group->id,
                newValues: [
                    'role' => $invitation->role->value,
                    'email' => $invitation->email,
                    'max_uses' => $invitation->max_uses,
                ],
            );

            return $invitation;
        });
    }

    /**
     * Accept a valid group invitation token.
     */
    public function acceptInvitation(string $token, User $user): GroupMembership
    {
        /** @var GroupInvitation|null $invitation */
        $invitation = GroupInvitation::query()->where('token', $token)->first();

        if ($invitation === null || ! $invitation->isValid()) {
            throw ValidationException::withMessages([
                'token' => ['This invitation is invalid, expired, or has reached its usage limit.'],
            ]);
        }

        if ($invitation->email !== null && strcasecmp($invitation->email, $user->email) !== 0) {
            throw ValidationException::withMessages([
                'token' => ['This invitation was issued for a different email address.'],
            ]);
        }

        return DB::transaction(function () use ($invitation, $user): GroupMembership {
            $group = $invitation->group;

            $existing = $group->membershipFor($user);
            if ($existing !== null && $existing->status === MembershipStatus::Banned) {
                throw ValidationException::withMessages([
                    'token' => ['You are banned from this group and cannot accept an invitation.'],
                ]);
            }

            $membership = GroupMembership::query()->updateOrCreate(
                [
                    'group_id' => $group->id,
                    'user_id' => $user->id,
                ],
                [
                    'role' => $invitation->role,
                    'status' => MembershipStatus::Active,
                    'invited_by' => $invitation->invited_by,
                    'approved_by' => $invitation->invited_by,
                    'joined_at' => now(),
                ]
            );

            GroupJoinRequest::query()
                ->where('group_id', $group->id)
                ->where('user_id', $user->id)
                ->where('status', JoinRequestStatus::Pending->value)
                ->update([
                    'status' => JoinRequestStatus::Approved->value,
                    'reviewed_by' => $invitation->invited_by,
                    'reviewed_at' => now(),
                ]);

            $invitation->increment('uses_count');

            $this->auditLogger->log(
                event: 'invitation.accepted',
                auditable: $membership,
                actor: $user,
                groupId: $group->id,
                newValues: [
                    'invitation_id' => $invitation->id,
                    'role' => $membership->role->value,
                ],
            );

            return $membership;
        });
    }
}
