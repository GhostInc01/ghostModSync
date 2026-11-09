<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_id',
        'version_branch',
        'is_current',
        'released_at',
    ];

    protected function casts(): array
    {
        return [
            'is_current' => 'boolean',
            'released_at' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Game, $this>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }
}
