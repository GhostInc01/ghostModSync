<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Group;
use App\Models\User;
use App\Services\Permissions\GroupPermissionService;

class GroupPolicy
{
    public function __construct(
        private readonly GroupPermissionService $permissions,
    ) {}

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Group $group): bool
    {
        return $this->permissions->canViewGroup($user, $group);
    }

    public function create(User $user): bool
    {
        return $user->hasVerifiedEmail();
    }

    public function update(User $user, Group $group): bool
    {
        return $this->permissions->canManageSettings($user, $group);
    }

    public function delete(User $user, Group $group): bool
    {
        return $this->permissions->canTransferOwnership($user, $group);
    }

    public function transferOwnership(User $user, Group $group): bool
    {
        return $this->permissions->canTransferOwnership($user, $group);
    }

    public function manageMembers(User $user, Group $group): bool
    {
        return $this->permissions->canManageMembers($user, $group);
    }

    public function assignRoles(User $user, Group $group): bool
    {
        return $this->permissions->canAssignRoles($user, $group);
    }

    public function manageMods(User $user, Group $group): bool
    {
        return $this->permissions->canManageMods($user, $group);
    }

    public function manageConvoyProfiles(User $user, Group $group): bool
    {
        return $this->permissions->canManageConvoyProfiles($user, $group);
    }

    public function approveSync(User $user, Group $group): bool
    {
        return $this->permissions->canApproveSync($user, $group);
    }
}
