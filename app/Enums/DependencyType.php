<?php

declare(strict_types=1);

namespace App\Enums;

enum DependencyType: string
{
    case Requires = 'requires';
    case ConflictsWith = 'conflicts_with';

    public function label(): string
    {
        return match ($this) {
            self::Requires => 'Requires',
            self::ConflictsWith => 'Conflicts With',
        };
    }
}
