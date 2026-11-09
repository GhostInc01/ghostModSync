<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DependencyType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModDependency extends Model
{
    protected $fillable = [
        'mod_version_id',
        'related_mod_id',
        'related_mod_version_id',
        'dependency_type',
    ];

    protected function casts(): array
    {
        return [
            'dependency_type' => DependencyType::class,
        ];
    }

    /**
     * @return BelongsTo<ModVersion, $this>
     */
    public function modVersion(): BelongsTo
    {
        return $this->belongsTo(ModVersion::class);
    }

    /**
     * @return BelongsTo<Mod, $this>
     */
    public function relatedMod(): BelongsTo
    {
        return $this->belongsTo(Mod::class, 'related_mod_id');
    }

    /**
     * @return BelongsTo<ModVersion, $this>
     */
    public function relatedModVersion(): BelongsTo
    {
        return $this->belongsTo(ModVersion::class, 'related_mod_version_id');
    }
}
