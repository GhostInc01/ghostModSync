<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\GameCode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Game extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'steam_app_id',
        'default_mod_dir_name',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'code' => GameCode::class,
            'steam_app_id' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<GameVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(GameVersion::class);
    }

    /**
     * @return HasMany<GameDlc, $this>
     */
    public function dlcs(): HasMany
    {
        return $this->hasMany(GameDlc::class);
    }
}
