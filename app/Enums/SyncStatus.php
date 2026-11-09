<?php

declare(strict_types=1);

namespace App\Enums;

enum SyncStatus: string
{
    case InSync = 'in_sync';
    case OutOfSync = 'out_of_sync';
    case Syncing = 'syncing';
    case PendingApproval = 'pending_approval';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::InSync => 'In Sync',
            self::OutOfSync => 'Out of Sync',
            self::Syncing => 'Synchronising',
            self::PendingApproval => 'Pending Approval',
            self::Failed => 'Sync Failed',
        };
    }
}
