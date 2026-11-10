<?php

declare(strict_types=1);

namespace App\Enums;

enum PlatformRole: string
{
    case SuperAdmin = 'super_admin';
    case PlatformAdmin = 'platform_admin';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Platform Super Admin',
            self::PlatformAdmin => 'Platform Administrator',
        };
    }

    public function canManagePlatformSettings(): bool
    {
        return $this === self::SuperAdmin;
    }
}
