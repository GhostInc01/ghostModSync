<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConvoyProfileRevisionMod extends Model
{
    protected $fillable = [
        'convoy_profile_revision_id',
        'mod_version_id',
        'load_order',
        'is_required',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'load_order' => 'integer',
            'is_required' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<ConvoyProfileRevision, $this>
     */
    public function revision(): BelongsTo
    {
        return $this->belongsTo(ConvoyProfileRevision::class, 'convoy_profile_revision_id');
    }

    /**
     * @return BelongsTo<ModVersion, $this>
     */
    public function modVersion(): BelongsTo
    {
        return $this->belongsTo(ModVersion::class);
    }
}
