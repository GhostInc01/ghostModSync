<?php

declare(strict_types=1);

namespace App\Enums;

enum GameCode: string
{
    case Ets2 = 'ets2';
    case Ats = 'ats';

    public function label(): string
    {
        return match ($this) {
            self::Ets2 => 'Euro Truck Simulator 2',
            self::Ats => 'American Truck Simulator',
        };
    }

    public function steamAppId(): int
    {
        return match ($this) {
            self::Ets2 => 227300,
            self::Ats => 270880,
        };
    }

    public function defaultModDirName(): string
    {
        return match ($this) {
            self::Ets2 => 'Euro Truck Simulator 2\\mod',
            self::Ats => 'American Truck Simulator\\mod',
        };
    }
}
