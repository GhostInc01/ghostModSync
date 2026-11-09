<?php

declare(strict_types=1);

namespace App\Enums;

enum SyncPolicyMode: string
{
    case Automatic = 'automatic';
    case ApprovalRequired = 'approval_required';

    public function label(): string
    {
        return match ($this) {
            self::Automatic => 'Automatic Synchronisation',
            self::ApprovalRequired => 'Approval-Based Synchronisation',
        };
    }
}
