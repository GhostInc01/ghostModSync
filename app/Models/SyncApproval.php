<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncApproval extends Model
{
    protected $fillable = [
        'convoy_profile_id',
        'convoy_profile_revision_id',
        'user_id',
        'device_id',
        'approved_revision',
        'status',
        'reviewed_by',
        'reviewed_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'approved_revision' => 'integer',
            'reviewed_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function isApprovedForRevision(int $revision): bool
    {
        if ($this->status !== 'approved' || $this->approved_revision !== $revision) {
            return false;
        }

        if ($this->expires_at !== null && $this->expires_at->isPast()) {
            return false;
        }

        return true;
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

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
