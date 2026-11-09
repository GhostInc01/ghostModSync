<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SyncItemAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncSessionItem extends Model
{
    protected $fillable = [
        'sync_session_id',
        'mod_version_id',
        'action_taken',
        'status',
        'local_sha256_hash',
        'bytes_downloaded',
        'error_detail',
    ];

    protected function casts(): array
    {
        return [
            'action_taken' => SyncItemAction::class,
            'bytes_downloaded' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<SyncSession, $this>
     */
    public function syncSession(): BelongsTo
    {
        return $this->belongsTo(SyncSession::class);
    }

    /**
     * @return BelongsTo<ModVersion, $this>
     */
    public function modVersion(): BelongsTo
    {
        return $this->belongsTo(ModVersion::class);
    }
}
