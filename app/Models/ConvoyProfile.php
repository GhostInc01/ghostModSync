<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SyncPolicyMode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ConvoyProfile extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'group_id',
        'game_id',
        'current_revision_id',
        'created_by',
        'name',
        'slug',
        'description',
        'target_game_version',
        'sync_policy_override',
        'revision',
        'manifest_hash',
        'is_default',
        'is_active',
        'scheduled_convoy_at',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'sync_policy_override' => SyncPolicyMode::class,
            'revision' => 'integer',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'scheduled_convoy_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function effectiveSyncPolicy(): SyncPolicyMode
    {
        return $this->sync_policy_override ?? $this->group->sync_policy;
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
     * @return HasMany<ConvoyProfileMod, $this>
     */
    public function profileMods(): HasMany
    {
        return $this->hasMany(ConvoyProfileMod::class)->orderBy('load_order');
    }

    /**
     * @return BelongsTo<ConvoyProfileRevision, $this>
     */
    public function currentRevision(): BelongsTo
    {
        return $this->belongsTo(ConvoyProfileRevision::class, 'current_revision_id');
    }

    /**
     * @return HasMany<ConvoyProfileRevision, $this>
     */
    public function revisions(): HasMany
    {
        return $this->hasMany(ConvoyProfileRevision::class)->orderByDesc('revision_number');
    }

    /**
     * @return BelongsToMany<ModVersion, $this>
     */
    public function modVersions(): BelongsToMany
    {
        return $this->belongsToMany(ModVersion::class, 'convoy_profile_mods')
            ->withPivot(['id', 'load_order', 'is_required', 'notes'])
            ->withTimestamps();
    }

    /**
     * @return HasMany<SyncApproval, $this>
     */
    public function syncApprovals(): HasMany
    {
        return $this->hasMany(SyncApproval::class);
    }

    /**
     * @return HasMany<SyncSession, $this>
     */
    public function syncSessions(): HasMany
    {
        return $this->hasMany(SyncSession::class);
    }
}
