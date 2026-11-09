<?php

declare(strict_types=1);

namespace App\Enums;

enum DlcCategory: string
{
    case Map = 'map';
    case Cargo = 'cargo';
    case Trailer = 'trailer';
    case Tuning = 'tuning';

    public function label(): string
    {
        return match ($this) {
            self::Map => 'Map Expansion',
            self::Cargo => 'Cargo Pack',
            self::Trailer => 'Trailer Pack',
            self::Tuning => 'Truck Tuning Pack',
        };
    }
}
