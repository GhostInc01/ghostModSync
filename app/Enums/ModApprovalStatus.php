<?php

declare(strict_types=1);

namespace App\Enums;

enum ModApprovalStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Revoked = 'revoked';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending QA Approval',
            self::Approved => 'Approved for Convoys',
            self::Rejected => 'Rejected',
            self::Revoked => 'Revoked',
        };
    }
}
