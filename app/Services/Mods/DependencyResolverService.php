<?php

declare(strict_types=1);

namespace App\Services\Mods;

use App\Enums\DependencyType;
use App\Models\ModDependency;
use App\Models\ModVersion;
use Illuminate\Validation\ValidationException;

class DependencyResolverService
{
    /**
     * Add a dependency or conflict rule to a ModVersion, rejecting self-dependencies and circular dependency chains.
     */
    public function addDependency(
        ModVersion $modVersion,
        int $relatedModId,
        DependencyType $type,
        ?int $relatedModVersionId = null,
    ): ModDependency {
        $mod = $modVersion->mod;

        if ($mod->id === $relatedModId) {
            throw ValidationException::withMessages([
                'related_mod_id' => ['A mod cannot depend on or conflict with itself.'],
            ]);
        }

        if ($type === DependencyType::Requires && $this->wouldCreateCycle($mod->id, $relatedModId)) {
            throw ValidationException::withMessages([
                'related_mod_id' => ['Circular mod dependency detected.'],
            ]);
        }

        return ModDependency::query()->updateOrCreate(
            [
                'mod_version_id' => $modVersion->id,
                'related_mod_id' => $relatedModId,
                'dependency_type' => $type,
            ],
            [
                'related_mod_version_id' => $relatedModVersionId,
            ]
        );
    }

    /**
     * Perform a Depth-First Search (DFS) on `requires` edges across mods to detect cycles.
     */
    public function wouldCreateCycle(int $sourceModId, int $targetModId): bool
    {
        $visited = [];
        $stack = [$targetModId];

        while ($stack !== []) {
            $currentModId = array_pop($stack);

            if ($currentModId === $sourceModId) {
                return true;
            }

            if (isset($visited[$currentModId])) {
                continue;
            }

            $visited[$currentModId] = true;

            $downstreamModIds = ModDependency::query()
                ->where('dependency_type', DependencyType::Requires->value)
                ->whereHas('modVersion', fn ($q) => $q->where('mod_id', $currentModId))
                ->pluck('related_mod_id')
                ->unique()
                ->all();

            foreach ($downstreamModIds as $nextModId) {
                if (! isset($visited[$nextModId])) {
                    $stack[] = (int) $nextModId;
                }
            }
        }

        return false;
    }

    /**
     * Validate that a list of ModVersion IDs for a ConvoyProfile satisfies all `requires` and `conflicts_with` rules.
     *
     * @param  list<int>  $modVersionIds
     */
    public function assertProfileDependenciesSatisfied(array $modVersionIds): void
    {
        if ($modVersionIds === []) {
            return;
        }

        $versions = ModVersion::query()
            ->with(['mod', 'dependencies.relatedMod'])
            ->whereIn('id', $modVersionIds)
            ->get();

        $includedModIds = $versions->pluck('mod_id')->unique()->all();
        $includedVersionIds = $versions->pluck('id')->unique()->all();

        foreach ($versions as $version) {
            foreach ($version->dependencies as $dependency) {
                if ($dependency->dependency_type === DependencyType::Requires) {
                    if (! in_array($dependency->related_mod_id, $includedModIds, true)) {
                        throw ValidationException::withMessages([
                            'mods' => [sprintf(
                                'Mod "%s" (%s) requires mod "%s", which is missing from this convoy profile.',
                                $version->mod->name,
                                $version->version_number,
                                $dependency->relatedMod->name
                            )],
                        ]);
                    }

                    if ($dependency->related_mod_version_id !== null && ! in_array($dependency->related_mod_version_id, $includedVersionIds, true)) {
                        throw ValidationException::withMessages([
                            'mods' => [sprintf(
                                'Mod "%s" (%s) requires a specific version of "%s" that is not selected in this profile.',
                                $version->mod->name,
                                $version->version_number,
                                $dependency->relatedMod->name
                            )],
                        ]);
                    }
                }

                if ($dependency->dependency_type === DependencyType::ConflictsWith) {
                    if (in_array($dependency->related_mod_id, $includedModIds, true)) {
                        throw ValidationException::withMessages([
                            'mods' => [sprintf(
                                'Mod "%s" (%s) conflicts with mod "%s" in this convoy profile.',
                                $version->mod->name,
                                $version->version_number,
                                $dependency->relatedMod->name
                            )],
                        ]);
                    }
                }
            }
        }
    }
}
