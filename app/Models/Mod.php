<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ModSourceType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Mod extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'group_id',
        'game_id',
        'created_by',
        'name',
        'slug',
        'category',
        'author_name',
        'description',
        'source_type',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'source_type' => ModSourceType::class,
            'is_active' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return BelongsTo<Group, $this>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * @return BelongsTo<Game, $this>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<ModVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(ModVersion::class);
    }

    /**
     * @return HasMany<ModVersion, $this>
     */
    public function modVersions(): HasMany
    {
        return $this->versions();
    }
}
