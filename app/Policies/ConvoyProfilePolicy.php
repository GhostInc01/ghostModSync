<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ConvoyProfile;
use App\Models\User;
use App\Services\Permissions\GroupPermissionService;

class ConvoyProfilePolicy
{
    public function __construct(
        private readonly GroupPermissionService $permissions,
    ) {}

    public function view(User $user, ConvoyProfile $profile): bool
    {
        return $this->permissions->canViewGroup($user, $profile->group);
    }

    public function update(User $user, ConvoyProfile $profile): bool
    {
        return $this->permissions->canManageConvoyProfiles($user, $profile->group);
    }

    public function delete(User $user, ConvoyProfile $profile): bool
    {
        return $this->permissions->canManageConvoyProfiles($user, $profile->group);
    }

    public function approveSync(User $user, ConvoyProfile $profile): bool
    {
        return $this->permissions->canApproveSync($user, $profile->group);
    }
}
