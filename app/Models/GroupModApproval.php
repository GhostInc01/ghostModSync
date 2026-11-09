<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ModApprovalStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupModApproval extends Model
{
    protected $fillable = [
        'group_id',
        'mod_version_id',
        'status',
        'checksum_verified',
        'reviewed_by',
        'review_notes',
        'approved_at',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ModApprovalStatus::class,
            'checksum_verified' => 'boolean',
            'approved_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function isApproved(): bool
    {
        return $this->status === ModApprovalStatus::Approved && $this->revoked_at === null;
    }

    /**
     * @return BelongsTo<Group, $this>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * @return BelongsTo<ModVersion, $this>
     */
    public function modVersion(): BelongsTo
    {
        return $this->belongsTo(ModVersion::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
