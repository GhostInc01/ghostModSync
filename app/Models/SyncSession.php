<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SyncStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SyncSession extends Model
{
    protected $fillable = [
        'convoy_profile_id',
        'convoy_profile_revision_id',
        'user_id',
        'device_id',
        'profile_revision',
        'reported_manifest_hash',
        'status',
        'missing_mods_count',
        'mismatched_mods_count',
        'extra_local_mods_count',
        'bytes_downloaded',
        'duration_ms',
        'error_message',
        'started_at',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'profile_revision' => 'integer',
            'status' => SyncStatus::class,
            'missing_mods_count' => 'integer',
            'mismatched_mods_count' => 'integer',
            'extra_local_mods_count' => 'integer',
            'bytes_downloaded' => 'integer',
            'duration_ms' => 'integer',
            'started_at' => 'datetime',
            'synced_at' => 'datetime',
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
     * @return BelongsTo<ConvoyProfileRevision, $this>
     */
    public function revision(): BelongsTo
    {
        return $this->belongsTo(ConvoyProfileRevision::class, 'convoy_profile_revision_id');
    }

    /**
     * @return HasMany<SyncSessionItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(SyncSessionItem::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Device, $this>
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
