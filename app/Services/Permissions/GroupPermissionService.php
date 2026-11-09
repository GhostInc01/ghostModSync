<?php

declare(strict_types=1);

namespace App\Services\Permissions;

use App\Enums\GroupRole;
use App\Enums\MembershipStatus;
use App\Models\Group;
use App\Models\User;

class GroupPermissionService
{
    public function isActiveMember(User $user, Group $group): bool
    {
        if ($user->is_platform_admin || $group->owner_id === $user->id) {
            return true;
        }

        return $group->memberships()
            ->where('user_id', $user->id)
            ->where('status', MembershipStatus::Active->value)
            ->exists();
    }

    public function roleFor(User $user, Group $group): ?GroupRole
    {
        if ($user->is_platform_admin || $group->owner_id === $user->id) {
            return GroupRole::Owner;
        }

        return $group->activeRoleFor($user);
    }

    public function canViewGroup(User $user, Group $group): bool
    {
        if ($group->isPublic()) {
            return true;
        }

        return $this->isActiveMember($user, $group);
    }

    public function canManageSettings(User $user, Group $group): bool
    {
        return $this->roleFor($user, $group)?->canManageGroupSettings() ?? false;
    }

    public function canManageMembers(User $user, Group $group): bool
    {
        return $this->roleFor($user, $group)?->canManageMembers() ?? false;
    }

    public function canAssignRoles(User $user, Group $group): bool
    {
        return $this->roleFor($user, $group)?->canAssignRoles() ?? false;
    }

    public function canTransferOwnership(User $user, Group $group): bool
    {
        return $user->is_platform_admin || $group->owner_id === $user->id;
    }

    public function canManageMods(User $user, Group $group): bool
    {
        return $this->roleFor($user, $group)?->canManageMods() ?? false;
    }

    public function canManageConvoyProfiles(User $user, Group $group): bool
    {
        return $this->roleFor($user, $group)?->canManageConvoyProfiles() ?? false;
    }

    public function canApproveSync(User $user, Group $group): bool
    {
        return $this->roleFor($user, $group)?->canApproveSync() ?? false;
    }
}
