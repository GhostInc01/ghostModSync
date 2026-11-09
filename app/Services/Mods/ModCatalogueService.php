<?php

declare(strict_types=1);

namespace App\Services\Mods;

use App\Enums\ModApprovalStatus;
use App\Enums\ModSourceType;
use App\Enums\ModVersionStatus;
use App\Models\Game;
use App\Models\GameDlc;
use App\Models\Group;
use App\Models\GroupModApproval;
use App\Models\Mod;
use App\Models\ModSource;
use App\Models\ModVersion;
use App\Models\User;
use App\Services\Audit\AuditLoggerService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ModCatalogueService
{
    public function __construct(
        private readonly S3ModStorageService $s3Storage,
        private readonly SteamWorkshopService $steamWorkshop,
        private readonly AuditLoggerService $auditLogger,
    ) {}

    /**
     * Create a new logical Mod in a group's catalogue.
     *
     * @param  array{
     *     game_code: string,
     *     name: string,
     *     category?: string|null,
     *     author_name?: string|null,
     *     description?: string|null,
     *     source_type: ModSourceType|string
     * }  $data
     */
    public function createMod(Group $group, User $actor, array $data): Mod
    {
        return DB::transaction(function () use ($group, $actor, $data): Mod {
            /** @var Game $game */
            $game = Game::query()->where('code', $data['game_code'])->firstOrFail();

            $sourceType = $data['source_type'] instanceof ModSourceType
                ? $data['source_type']
                : ModSourceType::from((string) $data['source_type']);

            $slug = Str::slug((string) $data['name']);
            if (Mod::withTrashed()->where('group_id', $group->id)->where('game_id', $game->id)->where('slug', $slug)->exists()) {
                $slug .= '-'.strtolower(Str::random(4));
            }

            $mod = Mod::query()->create([
                'group_id' => $group->id,
                'game_id' => $game->id,
                'created_by' => $actor->id,
                'name' => $data['name'],
                'slug' => $slug,
                'category' => $data['category'] ?? 'general',
                'author_name' => $data['author_name'] ?? null,
                'description' => $data['description'] ?? null,
                'source_type' => $sourceType,
                'is_active' => true,
            ]);

            $this->auditLogger->log(
                event: 'mod.created',
                auditable: $mod,
                actor: $actor,
                groupId: $group->id,
                newValues: [
                    'name' => $mod->name,
                    'game_code' => $game->code->value,
                    'source_type' => $mod->source_type->value,
                ],
            );

            return $mod;
        });
    }

    /**
     * Register a new ModVersion (either S3 direct upload or Steam Workshop reference).
     *
     * @param  array{
     *     version_number: string,
     *     min_game_version?: string|null,
     *     max_game_version?: string|null,
     *     archive_filename: string,
     *     file_size_bytes?: int|null,
     *     sha256_hash?: string|null,
     *     steam_published_file_id?: string|null,
     *     changelog?: string|null
     * }  $data
     * @return array{version: ModVersion, upload?: array{upload_url: string, headers: array<string, mixed>, expires_at: string}}
     */
    public function createModVersion(Mod $mod, User $actor, array $data): array
    {
        $group = $mod->group;
        $sanitizedFilename = $this->s3Storage->sanitizeFilename((string) $data['archive_filename']);

        if ($mod->source_type === ModSourceType::S3Upload) {
            $fileSize = (int) ($data['file_size_bytes'] ?? 0);
            $sha256 = strtolower((string) ($data['sha256_hash'] ?? ''));

            if ($fileSize <= 0) {
                throw ValidationException::withMessages([
                    'file_size_bytes' => ['File size in bytes is required for S3 mod uploads.'],
                ]);
            }

            if (! preg_match('/^[a-f0-9]{64}$/', $sha256)) {
                throw ValidationException::withMessages([
                    'sha256_hash' => ['A valid 64-character lowercase hexadecimal SHA-256 hash is required for S3 mod uploads.'],
                ]);
            }

            $this->s3Storage->assertStorageQuotaAvailable($group, $fileSize);

            return DB::transaction(function () use ($group, $mod, $actor, $data, $sanitizedFilename, $fileSize, $sha256): array {
                $objectKey = $this->s3Storage->buildObjectKey(
                    group: $group,
                    mod: $mod,
                    versionNumber: (string) $data['version_number'],
                    sanitizedFilename: $sanitizedFilename,
                );

                $version = ModVersion::query()->create([
                    'mod_id' => $mod->id,
                    'uploaded_by' => $actor->id,
                    'version_number' => $data['version_number'],
                    'min_game_version' => $data['min_game_version'] ?? null,
                    'max_game_version' => $data['max_game_version'] ?? null,
                    'archive_filename' => $sanitizedFilename,
                    'file_size_bytes' => $fileSize,
                    'sha256_hash' => $sha256,
                    'changelog' => $data['changelog'] ?? null,
                    'status' => ModVersionStatus::Ready,
                    'published_at' => now(),
                ]);

                ModSource::query()->create([
                    'mod_version_id' => $version->id,
                    'source_type' => ModSourceType::S3Upload,
                    's3_disk' => 's3',
                    's3_object_key' => $objectKey,
                ]);

                $this->syncRequiredDlcs($version, $data['required_dlc_codes'] ?? []);

                GroupModApproval::query()->updateOrCreate(
                    [
                        'group_id' => $group->id,
                        'mod_version_id' => $version->id,
                    ],
                    [
                        'status' => ModApprovalStatus::Approved,
                        'checksum_verified' => true,
                        'reviewed_by' => $actor->id,
                        'review_notes' => 'Auto-approved upon S3 SHA-256 verification by authorized mod manager.',
                        'approved_at' => now(),
                    ]
                );

                $uploadInstructions = $this->s3Storage->createTemporaryUploadUrl($objectKey);

                $this->auditLogger->log(
                    event: 'mod_version.published_s3',
                    auditable: $version,
                    actor: $actor,
                    groupId: $group->id,
                    newValues: [
                        'mod_id' => $mod->id,
                        'version_number' => $version->version_number,
                        'archive_filename' => $version->archive_filename,
                        'file_size_bytes' => $version->file_size_bytes,
                        'sha256_hash' => $version->sha256_hash,
                        's3_object_key' => $objectKey,
                    ],
                );

                return [
                    'version' => $version->load('source'),
                    'upload' => $uploadInstructions,
                ];
            });
        }

        // Steam Workshop ModVersion
        $publishedFileId = trim((string) ($data['steam_published_file_id'] ?? ''));
        if (! preg_match('/^\d{5,25}$/', $publishedFileId)) {
            throw ValidationException::withMessages([
                'steam_published_file_id' => ['A valid numeric Steam Workshop PublishedFileId is required.'],
            ]);
        }

        $workshopDetails = $this->steamWorkshop->fetchWorkshopItemDetails($publishedFileId);

        return DB::transaction(function () use ($group, $mod, $actor, $data, $sanitizedFilename, $publishedFileId, $workshopDetails): array {
            $version = ModVersion::query()->create([
                'mod_id' => $mod->id,
                'uploaded_by' => $actor->id,
                'version_number' => $data['version_number'],
                'min_game_version' => $data['min_game_version'] ?? null,
                'max_game_version' => $data['max_game_version'] ?? null,
                'archive_filename' => $sanitizedFilename,
                'file_size_bytes' => $data['file_size_bytes'] ?? $workshopDetails['file_size_bytes'],
                'sha256_hash' => ! empty($data['sha256_hash']) ? strtolower((string) $data['sha256_hash']) : null,
                'changelog' => $data['changelog'] ?? null,
                'status' => ModVersionStatus::Ready,
                'published_at' => now(),
            ]);

            ModSource::query()->create([
                'mod_version_id' => $version->id,
                'source_type' => ModSourceType::SteamWorkshop,
                'steam_published_file_id' => $publishedFileId,
                'steam_workshop_url' => $workshopDetails['workshop_url'],
                'steam_last_synced_at' => now(),
            ]);

            $this->syncRequiredDlcs($version, $data['required_dlc_codes'] ?? []);

            GroupModApproval::query()->updateOrCreate(
                [
                    'group_id' => $group->id,
                    'mod_version_id' => $version->id,
                ],
                [
                    'status' => ModApprovalStatus::Approved,
                    'checksum_verified' => ! empty($version->sha256_hash),
                    'reviewed_by' => $actor->id,
                    'review_notes' => 'Approved Steam Workshop reference by authorized mod manager.',
                    'approved_at' => now(),
                ]
            );

            $this->auditLogger->log(
                event: 'mod_version.published_steam_workshop',
                auditable: $version,
                actor: $actor,
                groupId: $group->id,
                newValues: [
                    'mod_id' => $mod->id,
                    'version_number' => $version->version_number,
                    'steam_published_file_id' => $publishedFileId,
                ],
            );

            return [
                'version' => $version->load(['source', 'requiredDlcs', 'groupApprovals']),
            ];
        });
    }

    /**
     * Attach required official SCS DLCs to a ModVersion.
     *
     * @param  list<string>  $dlcCodes
     */
    public function syncRequiredDlcs(ModVersion $version, array $dlcCodes): void
    {
        if ($dlcCodes === []) {
            return;
        }

        $dlcs = GameDlc::query()
            ->where('game_id', $version->mod->game_id)
            ->whereIn('code', $dlcCodes)
            ->pluck('id')
            ->all();

        $pivotData = [];
        foreach ($dlcs as $dlcId) {
            $pivotData[(int) $dlcId] = ['is_required' => true];
        }

        $version->requiredDlcs()->sync($pivotData);
    }

    /**
     * Update a group's approval status for a specific ModVersion.
     */
    public function updateGroupModApproval(
        Group $group,
        ModVersion $version,
        User $reviewer,
        ModApprovalStatus $status,
        ?string $notes = null,
    ): GroupModApproval {
        return DB::transaction(function () use ($group, $version, $reviewer, $status, $notes): GroupModApproval {
            $approval = GroupModApproval::query()->updateOrCreate(
                [
                    'group_id' => $group->id,
                    'mod_version_id' => $version->id,
                ],
                [
                    'status' => $status,
                    'checksum_verified' => ! empty($version->sha256_hash),
                    'reviewed_by' => $reviewer->id,
                    'review_notes' => $notes,
                    'approved_at' => $status === ModApprovalStatus::Approved ? now() : null,
                    'revoked_at' => $status === ModApprovalStatus::Revoked ? now() : null,
                ]
            );

            $this->auditLogger->log(
                event: 'group_mod_approval.'.$status->value,
                auditable: $approval,
                actor: $reviewer,
                groupId: $group->id,
                newValues: [
                    'mod_version_id' => $version->id,
                    'status' => $status->value,
                ],
            );

            return $approval;
        });
    }
}
