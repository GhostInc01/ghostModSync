<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Models\ConvoyProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ConvoyProfile
 */
class ConvoyProfileManifestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $this->loadMissing([
            'group',
            'game',
            'profileMods.modVersion.mod',
            'profileMods.modVersion.source',
            'profileMods.modVersion.dependencies.relatedMod',
            'profileMods.modVersion.requiredDlcs',
        ]);

        return [
            'profile_id' => $this->id,
            'group_slug' => $this->group->slug,
            'group_name' => $this->group->name,
            'name' => $this->name,
            'slug' => $this->slug,
            'game_code' => $this->game->code->value,
            'steam_app_id' => $this->game->steam_app_id,
            'default_mod_dir_name' => $this->game->default_mod_dir_name,
            'target_game_version' => $this->target_game_version,
            'sync_policy' => $this->effectiveSyncPolicy()->value,
            'revision' => $this->revision,
            'current_revision_id' => $this->current_revision_id,
            'manifest_hash' => $this->manifest_hash,
            'is_default' => $this->is_default,
            'scheduled_convoy_at' => $this->scheduled_convoy_at?->toIso8601ZuluString(),
            'published_at' => $this->published_at?->toIso8601ZuluString(),
            'mods' => $this->profileMods->map(function ($profileMod) {
                $version = $profileMod->modVersion;
                $mod = $version->mod;
                $source = $version->source;

                return [
                    'load_order' => $profileMod->load_order,
                    'is_required' => $profileMod->is_required,
                    'notes' => $profileMod->notes,
                    'mod_id' => $mod->id,
                    'mod_slug' => $mod->slug,
                    'mod_name' => $mod->name,
                    'category' => $mod->category,
                    'mod_version_id' => $version->id,
                    'version_number' => $version->version_number,
                    'archive_filename' => $version->archive_filename,
                    'file_size_bytes' => $version->file_size_bytes,
                    'sha256_hash' => $version->sha256_hash,
                    'source_type' => $source?->source_type->value ?? $mod->source_type->value,
                    'steam_published_file_id' => $source?->steam_published_file_id,
                    'steam_workshop_url' => $source?->steam_workshop_url,
                    'required_dlcs' => $version->requiredDlcs->map(fn ($dlc) => [
                        'code' => $dlc->code,
                        'name' => $dlc->name,
                        'steam_app_id' => $dlc->steam_app_id,
                        'category' => $dlc->category->value,
                    ])->values()->all(),
                    'dependencies' => $version->dependencies->map(fn ($dep) => [
                        'type' => $dep->dependency_type->value,
                        'related_mod_id' => $dep->related_mod_id,
                        'related_mod_slug' => $dep->relatedMod->slug,
                        'related_mod_version_id' => $dep->related_mod_version_id,
                    ])->values()->all(),
                ];
            })->values()->all(),
        ];
    }
}
