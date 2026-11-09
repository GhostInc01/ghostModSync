<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ModVersionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class ModVersion extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'mod_id',
        'uploaded_by',
        'version_number',
        'min_game_version',
        'max_game_version',
        'archive_filename',
        'file_size_bytes',
        'sha256_hash',
        'changelog',
        'status',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'file_size_bytes' => 'integer',
            'status' => ModVersionStatus::class,
            'published_at' => 'datetime',
        ];
    }

    public function isReady(): bool
    {
        return $this->status === ModVersionStatus::Ready;
    }

    /**
     * @return BelongsTo<Mod, $this>
     */
    public function mod(): BelongsTo
    {
        return $this->belongsTo(Mod::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * @return HasOne<ModSource, $this>
     */
    public function source(): HasOne
    {
        return $this->hasOne(ModSource::class);
    }

    /**
     * @return HasMany<ModDependency, $this>
     */
    public function dependencies(): HasMany
    {
        return $this->hasMany(ModDependency::class);
    }

    /**
     * @return BelongsToMany<GameDlc, $this>
     */
    public function requiredDlcs(): BelongsToMany
    {
        return $this->belongsToMany(GameDlc::class, 'mod_version_dlc')
            ->withPivot(['id', 'is_required'])
            ->withTimestamps();
    }

    /**
     * @return HasMany<GroupModApproval, $this>
     */
    public function groupApprovals(): HasMany
    {
        return $this->hasMany(GroupModApproval::class);
    }
}
