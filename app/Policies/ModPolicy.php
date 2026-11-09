<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Mod;
use App\Models\User;
use App\Services\Permissions\GroupPermissionService;

class ModPolicy
{
    public function __construct(
        private readonly GroupPermissionService $permissions,
    ) {}

    public function view(User $user, Mod $mod): bool
    {
        return $this->permissions->canViewGroup($user, $mod->group);
    }

    public function update(User $user, Mod $mod): bool
    {
        return $this->permissions->canManageMods($user, $mod->group);
    }

    public function delete(User $user, Mod $mod): bool
    {
        return $this->permissions->canManageMods($user, $mod->group);
    }
}
