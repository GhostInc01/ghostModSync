<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConvoyProfileRevision extends Model
{
    protected $fillable = [
        'convoy_profile_id',
        'revision_number',
        'published_by',
        'target_game_version',
        'manifest_hash',
        'changelog',
        'snapshot_json',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'revision_number' => 'integer',
            'snapshot_json' => 'array',
            'published_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<ConvoyProfile, $this>
     */
    public function convoyProfile(): BelongsTo
    {
        return $this->belongsTo(ConvoyProfile::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    /**
     * @return HasMany<ConvoyProfileRevisionMod, $this>
     */
    public function revisionMods(): HasMany
    {
        return $this->hasMany(ConvoyProfileRevisionMod::class)->orderBy('load_order');
    }

    /**
     * @return HasMany<SyncSession, $this>
     */
    public function syncSessions(): HasMany
    {
        return $this->hasMany(SyncSession::class);
    }
}
