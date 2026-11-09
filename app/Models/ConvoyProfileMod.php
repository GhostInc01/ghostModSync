<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConvoyProfileMod extends Model
{
    protected $fillable = [
        'convoy_profile_id',
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
     * @return BelongsTo<ConvoyProfile, $this>
     */
    public function convoyProfile(): BelongsTo
    {
        return $this->belongsTo(ConvoyProfile::class);
    }

    /**
     * @return BelongsTo<ModVersion, $this>
     */
    public function modVersion(): BelongsTo
    {
        return $this->belongsTo(ModVersion::class);
    }
}
