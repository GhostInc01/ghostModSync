<?php

declare(strict_types=1);

namespace App\Enums;

enum ModSourceType: string
{
    case S3Upload = 's3_upload';
    case SteamWorkshop = 'steam_workshop';

    public function label(): string
    {
        return match ($this) {
            self::S3Upload => 'Direct Upload (S3)',
            self::SteamWorkshop => 'Steam Workshop',
        };
    }
}
