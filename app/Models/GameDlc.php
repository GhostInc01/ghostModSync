<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DlcCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class GameDlc extends Model
{
    protected $fillable = [
        'game_id',
        'code',
        'name',
        'steam_app_id',
        'category',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'steam_app_id' => 'integer',
            'category' => DlcCategory::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Game, $this>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    /**
     * @return BelongsToMany<ModVersion, $this>
     */
    public function modVersions(): BelongsToMany
    {
        return $this->belongsToMany(ModVersion::class, 'mod_version_dlc')
            ->withPivot(['id', 'is_required'])
            ->withTimestamps();
    }
}
