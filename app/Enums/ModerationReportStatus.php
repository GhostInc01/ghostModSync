<?php

declare(strict_types=1);

namespace App\Enums;

enum ModerationReportStatus: string
{
    case Pending = 'pending';
    case Resolved = 'resolved';
    case Dismissed = 'dismissed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending Review',
            self::Resolved => 'Resolved',
            self::Dismissed => 'Dismissed',
        };
    }
}
