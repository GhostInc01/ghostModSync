<?php

declare(strict_types=1);

namespace App\Enums;

enum MembershipStatus: string
{
    case Active = 'active';
    case Pending = 'pending';
    case Invited = 'invited';
    case Suspended = 'suspended';
    case Banned = 'banned';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Pending => 'Pending Approval',
            self::Invited => 'Invited',
            self::Suspended => 'Suspended',
            self::Banned => 'Banned',
        };
    }
}
