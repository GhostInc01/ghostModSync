<?php

declare(strict_types=1);

namespace App\Enums;

enum ModVersionStatus: string
{
    case Pending = 'pending';
    case Ready = 'ready';
    case Deprecated = 'deprecated';
    case Revoked = 'revoked';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending Upload',
            self::Ready => 'Ready',
            self::Deprecated => 'Deprecated',
            self::Revoked => 'Revoked',
        };
    }
}
