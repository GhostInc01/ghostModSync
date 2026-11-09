<?php

declare(strict_types=1);

namespace App\Services\Profiles;

use App\Enums\ModApprovalStatus;
use App\Enums\SyncPolicyMode;
use App\Models\ConvoyProfile;
use App\Models\ConvoyProfileMod;
use App\Models\ConvoyProfileRevision;
use App\Models\ConvoyProfileRevisionMod;
use App\Models\Game;
use App\Models\Group;
use App\Models\ModVersion;
use App\Models\User;
use App\Services\Audit\AuditLoggerService;
use App\Services\Mods\DependencyResolverService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ConvoyProfileService
{
    public function __construct(
        private readonly DependencyResolverService $dependencyResolver,
        private readonly AuditLoggerService $auditLogger,
    ) {}

    /**
     * Compute a deterministic 64-character SHA-256 manifest hash over a canonical ordered list of mod versions.
     *
     * @param  list<array{load_order: int, mod_version_id: int, is_required: bool, sha256_hash: string|null, steam_published_file_id: string|null}>  $canonicalEntries
     */
    public function computeManifestHash(string $gameCode, string $targetGameVersion, array $canonicalEntries): string
    {
        $payload = [
            'game_code' => $gameCode,
            'target_game_version' => $targetGameVersion,
            'mods' => $canonicalEntries,
        ];

        return hash('sha256', (string) json_encode($payload, JSON_UNESCAPED_SLASHES));
    }

    /**
     * Create a new ConvoyProfile and record its initial Revision #1 snapshot.
     *
     * @param  array{
     *     game_code: string,
     *     name: string,
     *     description?: string|null,
     *     target_game_version: string,
     *     sync_policy_override?: string|null,
     *     is_default?: bool,
     *     scheduled_convoy_at?: string|null,
     *     changelog?: string|null,
     *     mods?: list<array{mod_version_id: int, is_required?: bool, notes?: string|null}>
     * }  $data
     */
    public function createProfile(Group $group, User $actor, array $data): ConvoyProfile
    {
        return DB::transaction(function () use ($group, $actor, $data): ConvoyProfile {
            /** @var Game $game */
            $game = Game::query()->where('code', $data['game_code'])->firstOrFail();

            $slug = Str::slug((string) $data['name']);
            if (ConvoyProfile::withTrashed()->where('group_id', $group->id)->where('slug', $slug)->exists()) {
                $slug .= '-'.strtolower(Str::random(4));
            }

            $syncPolicyOverride = ! empty($data['sync_policy_override'])
                ? SyncPolicyMode::from((string) $data['sync_policy_override'])
                : null;

            $emptyHash = $this->computeManifestHash($game->code->value, (string) $data['target_game_version'], []);

            $profile = ConvoyProfile::query()->create([
                'group_id' => $group->id,
                'game_id' => $game->id,
                'created_by' => $actor->id,
                'name' => $data['name'],
                'slug' => $slug,
                'description' => $data['description'] ?? null,
                'target_game_version' => $data['target_game_version'],
                'sync_policy_override' => $syncPolicyOverride,
                'revision' => 1,
                'manifest_hash' => $emptyHash,
                'is_default' => (bool) ($data['is_default'] ?? false),
                'is_active' => true,
                'scheduled_convoy_at' => $data['scheduled_convoy_at'] ?? null,
                'published_at' => now(),
            ]);

            $modsList = (! empty($data['mods']) && is_array($data['mods'])) ? $data['mods'] : [];

            $this->syncProfileMods(
                profile: $profile,
                actor: $actor,
                orderedModItems: $modsList,
                incrementRevision: false,
                changelog: $data['changelog'] ?? 'Initial convoy profile publication.',
            );

            $this->auditLogger->log(
                event: 'convoy_profile.created',
                auditable: $profile,
                actor: $actor,
                groupId: $group->id,
                newValues: [
                    'name' => $profile->name,
                    'game_code' => $game->code->value,
                    'target_game_version' => $profile->target_game_version,
                    'manifest_hash' => $profile->manifest_hash,
                    'current_revision_id' => $profile->current_revision_id,
                ],
            );

            return $profile->fresh([
                'game',
                'currentRevision.revisionMods.modVersion.mod',
                'profileMods.modVersion.mod',
                'profileMods.modVersion.source',
            ]) ?? $profile;
        });
    }

    /**
     * Replace the ordered list of ModVersions in a ConvoyProfile, validating group/game isolation,
     * group-specific mod approvals, and DAG dependencies, and publishing an immutable ConvoyProfileRevision snapshot.
     *
     * @param  list<array{mod_version_id: int, is_required?: bool, notes?: string|null}>  $orderedModItems
     */
    public function syncProfileMods(
        ConvoyProfile $profile,
        User $actor,
        array $orderedModItems,
        bool $incrementRevision = true,
        ?string $changelog = null,
    ): ConvoyProfile {
        return DB::transaction(function () use ($profile, $actor, $orderedModItems, $incrementRevision, $changelog): ConvoyProfile {
            $profile->loadMissing(['group', 'game']);
            $modVersionIds = array_values(array_map(fn (array $item) => (int) $item['mod_version_id'], $orderedModItems));

            if (count($modVersionIds) !== count(array_unique($modVersionIds))) {
                throw ValidationException::withMessages([
                    'mods' => ['Duplicate mod versions are not allowed in the same convoy profile.'],
                ]);
            }

            $versions = ModVersion::query()
                ->with(['mod', 'source', 'requiredDlcs', 'groupApprovals' => fn ($q) => $q->where('group_id', $profile->group_id)])
                ->whereIn('id', $modVersionIds)
                ->get()
                ->keyBy('id');

            $seenModIds = [];
            foreach ($modVersionIds as $versionId) {
                /** @var ModVersion|null $version */
                $version = $versions->get($versionId);

                if ($version === null) {
                    throw ValidationException::withMessages([
                        'mods' => ['One or more selected mod versions do not exist.'],
                    ]);
                }

                if ($version->mod->group_id !== $profile->group_id || $version->mod->game_id !== $profile->game_id) {
                    throw ValidationException::withMessages([
                        'mods' => ['All mod versions in a convoy profile must belong to the same group and game.'],
                    ]);
                }

                if ($profile->group->require_mod_approval) {
                    $approval = $version->groupApprovals->first();
                    if ($approval === null || $approval->status !== ModApprovalStatus::Approved || ! $approval->isApproved()) {
                        throw ValidationException::withMessages([
                            'mods' => [sprintf('Mod "%s" (%s) has not been approved for convoy use in this group.', $version->mod->name, $version->version_number)],
                        ]);
                    }
                }

                if (isset($seenModIds[$version->mod_id])) {
                    throw ValidationException::withMessages([
                        'mods' => [sprintf('Multiple versions of mod "%s" cannot be active in the same profile.', $version->mod->name)],
                    ]);
                }

                $seenModIds[$version->mod_id] = true;
            }

            // Verify DAG dependencies & conflicts
            $this->dependencyResolver->assertProfileDependenciesSatisfied($modVersionIds);

            $profile->profileMods()->delete();

            $canonicalEntries = [];
            $snapshotMods = [];

            foreach (array_values($orderedModItems) as $index => $item) {
                $loadOrder = $index + 1;
                $versionId = (int) $item['mod_version_id'];
                /** @var ModVersion $version */
                $version = $versions->get($versionId);
                $isRequired = (bool) ($item['is_required'] ?? true);
                $notes = $item['notes'] ?? null;

                ConvoyProfileMod::query()->create([
                    'convoy_profile_id' => $profile->id,
                    'mod_version_id' => $version->id,
                    'load_order' => $loadOrder,
                    'is_required' => $isRequired,
                    'notes' => $notes,
                ]);

                $canonicalEntries[] = [
                    'load_order' => $loadOrder,
                    'mod_version_id' => $version->id,
                    'is_required' => $isRequired,
                    'sha256_hash' => $version->sha256_hash,
                    'steam_published_file_id' => $version->source?->steam_published_file_id,
                ];

                $snapshotMods[] = [
                    'load_order' => $loadOrder,
                    'is_required' => $isRequired,
                    'notes' => $notes,
                    'mod_id' => $version->mod->id,
                    'mod_name' => $version->mod->name,
                    'mod_slug' => $version->mod->slug,
                    'mod_version_id' => $version->id,
                    'version_number' => $version->version_number,
                    'archive_filename' => $version->archive_filename,
                    'file_size_bytes' => $version->file_size_bytes,
                    'sha256_hash' => $version->sha256_hash,
                    'source_type' => $version->source?->source_type->value ?? $version->mod->source_type->value,
                    'steam_published_file_id' => $version->source?->steam_published_file_id,
                    'required_dlcs' => $version->requiredDlcs->map(fn ($dlc) => [
                        'code' => $dlc->code,
                        'name' => $dlc->name,
                        'steam_app_id' => $dlc->steam_app_id,
                    ])->values()->all(),
                ];
            }

            $newManifestHash = $this->computeManifestHash(
                gameCode: $profile->game->code->value,
                targetGameVersion: $profile->target_game_version,
                canonicalEntries: $canonicalEntries,
            );

            $newRevisionNumber = ($incrementRevision && $newManifestHash !== $profile->manifest_hash)
                ? $profile->revision + 1
                : $profile->revision;

            // Create or update the immutable ConvoyProfileRevision snapshot for this revision_number
            $revisionRecord = ConvoyProfileRevision::query()->updateOrCreate(
                [
                    'convoy_profile_id' => $profile->id,
                    'revision_number' => $newRevisionNumber,
                ],
                [
                    'published_by' => $actor->id,
                    'target_game_version' => $profile->target_game_version,
                    'manifest_hash' => $newManifestHash,
                    'changelog' => $changelog ?? ($newRevisionNumber === 1 ? 'Initial convoy profile publication.' : 'Updated convoy profile mod order/versions.'),
                    'snapshot_json' => [
                        'profile_id' => $profile->id,
                        'revision_number' => $newRevisionNumber,
                        'game_code' => $profile->game->code->value,
                        'target_game_version' => $profile->target_game_version,
                        'manifest_hash' => $newManifestHash,
                        'mods' => $snapshotMods,
                    ],
                    'published_at' => now(),
                ]
            );

            $revisionRecord->revisionMods()->delete();

            foreach (array_values($orderedModItems) as $index => $item) {
                ConvoyProfileRevisionMod::query()->create([
                    'convoy_profile_revision_id' => $revisionRecord->id,
                    'mod_version_id' => (int) $item['mod_version_id'],
                    'load_order' => $index + 1,
                    'is_required' => (bool) ($item['is_required'] ?? true),
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            $profile->update([
                'current_revision_id' => $revisionRecord->id,
                'revision' => $newRevisionNumber,
                'manifest_hash' => $newManifestHash,
                'published_at' => now(),
            ]);

            $this->auditLogger->log(
                event: 'convoy_profile.manifest_updated',
                auditable: $profile,
                actor: $actor,
                groupId: $profile->group_id,
                newValues: [
                    'revision' => $profile->revision,
                    'current_revision_id' => $revisionRecord->id,
                    'manifest_hash' => $profile->manifest_hash,
                    'mods_count' => count($canonicalEntries),
                ],
            );

            return $profile->fresh([
                'game',
                'currentRevision.revisionMods.modVersion.mod',
                'profileMods.modVersion.mod',
                'profileMods.modVersion.source',
            ]) ?? $profile;
        });
    }
}
