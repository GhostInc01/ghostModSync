<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Device;
use App\Models\User;

class DevicePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Device $device): bool
    {
        return $user->id === $device->user_id || $user->is_platform_admin;
    }

    public function create(User $user): bool
    {
        return $user->hasVerifiedEmail();
    }

    public function update(User $user, Device $device): bool
    {
        return $user->id === $device->user_id || $user->is_platform_admin;
    }

    public function revoke(User $user, Device $device): bool
    {
        return $user->id === $device->user_id || $user->is_platform_admin;
    }

    public function delete(User $user, Device $device): bool
    {
        return $user->id === $device->user_id || $user->is_platform_admin;
    }
}
