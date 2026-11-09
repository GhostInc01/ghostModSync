<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ModSourceType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModSource extends Model
{
    protected $fillable = [
        'mod_version_id',
        'source_type',
        's3_disk',
        's3_object_key',
        's3_etag',
        'steam_published_file_id',
        'steam_workshop_url',
        'steam_last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'source_type' => ModSourceType::class,
            'steam_last_synced_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<ModVersion, $this>
     */
    public function modVersion(): BelongsTo
    {
        return $this->belongsTo(ModVersion::class);
    }
}
