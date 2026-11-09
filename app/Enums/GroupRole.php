<?php

declare(strict_types=1);

namespace App\Enums;

enum GroupRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Moderator = 'moderator';
    case ModManager = 'mod_manager';
    case ConvoyDispatcher = 'convoy_dispatcher';
    case Member = 'member';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Group Owner',
            self::Admin => 'Administrator',
            self::Moderator => 'Moderator',
            self::ModManager => 'Mod Manager',
            self::ConvoyDispatcher => 'Convoy Dispatcher',
            self::Member => 'Member',
        };
    }

    /**
     * Numeric hierarchy rank for comparing role authority within a group.
     */
    public function rank(): int
    {
        return match ($this) {
            self::Owner => 100,
            self::Admin => 80,
            self::Moderator => 60,
            self::ModManager => 50,
            self::ConvoyDispatcher => 40,
            self::Member => 10,
        };
    }

    public function canManageGroupSettings(): bool
    {
        return in_array($this, [self::Owner, self::Admin], true);
    }

    public function canManageMembers(): bool
    {
        return in_array($this, [self::Owner, self::Admin, self::Moderator], true);
    }

    public function canAssignRoles(): bool
    {
        return in_array($this, [self::Owner, self::Admin], true);
    }

    public function canManageMods(): bool
    {
        return in_array($this, [self::Owner, self::Admin, self::ModManager], true);
    }

    public function canManageConvoyProfiles(): bool
    {
        return in_array($this, [self::Owner, self::Admin, self::ModManager, self::ConvoyDispatcher], true);
    }

    public function canApproveSync(): bool
    {
        return in_array($this, [self::Owner, self::Admin, self::Moderator, self::ConvoyDispatcher], true);
    }
}
