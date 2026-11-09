<?php

declare(strict_types=1);

namespace App\Services\Sync;

use App\Enums\ModSourceType;
use App\Enums\SyncItemAction;
use App\Enums\SyncPolicyMode;
use App\Enums\SyncStatus;
use App\Models\ConvoyProfile;
use App\Models\Device;
use App\Models\ModVersion;
use App\Models\SyncApproval;
use App\Models\SyncSession;
use App\Models\SyncSessionItem;
use App\Models\User;
use App\Services\Audit\AuditLoggerService;
use App\Services\Mods\S3ModStorageService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class SyncCoordinatorService
{
    public function __construct(
        private readonly S3ModStorageService $s3Storage,
        private readonly AuditLoggerService $auditLogger,
    ) {}

    /**
     * Check whether a user is currently approved to synchronise a ConvoyProfile.
     */
    public function hasValidApproval(ConvoyProfile $profile, User $user): bool
    {
        if ($profile->effectiveSyncPolicy() === SyncPolicyMode::Automatic) {
            return true;
        }

        // Group owners/admins/dispatchers are automatically approved
        if ($profile->group->activeRoleFor($user)?->canApproveSync()) {
            return true;
        }

        $approval = SyncApproval::query()
            ->where('convoy_profile_id', $profile->id)
            ->where('user_id', $user->id)
            ->where('status', 'approved')
            ->where('approved_revision', $profile->revision)
            ->first();

        return $approval !== null && $approval->isApprovedForRevision($profile->revision);
    }

    /**
     * Request synchronisation approval for an approval-gated ConvoyProfile.
     */
    public function requestSyncApproval(ConvoyProfile $profile, User $user, ?Device $device = null): SyncApproval
    {
        return DB::transaction(function () use ($profile, $user, $device): SyncApproval {
            $approval = SyncApproval::query()->updateOrCreate(
                [
                    'convoy_profile_id' => $profile->id,
                    'user_id' => $user->id,
                    'approved_revision' => $profile->revision,
                ],
                [
                    'convoy_profile_revision_id' => $profile->current_revision_id,
                    'device_id' => $device?->id,
                    'status' => 'pending',
                ]
            );

            $this->auditLogger->log(
                event: 'sync_approval.requested',
                auditable: $approval,
                actor: $user,
                groupId: $profile->group_id,
                newValues: [
                    'convoy_profile_id' => $profile->id,
                    'approved_revision' => $profile->revision,
                ],
                device: $device,
            );

            return $approval;
        });
    }

    /**
     * Approve or reject a pending synchronisation request.
     */
    public function reviewSyncApproval(SyncApproval $approval, User $reviewer, string $status): SyncApproval
    {
        if (! in_array($status, ['approved', 'rejected'], true)) {
            throw ValidationException::withMessages([
                'status' => ['Approval status must be approved or rejected.'],
            ]);
        }

        return DB::transaction(function () use ($approval, $reviewer, $status): SyncApproval {
            $approval->update([
                'status' => $status,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'expires_at' => $status === 'approved' ? now()->addDays(14) : null,
            ]);

            $this->auditLogger->log(
                event: 'sync_approval.'.$status,
                auditable: $approval,
                actor: $reviewer,
                groupId: $approval->convoyProfile->group_id,
                newValues: [
                    'user_id' => $approval->user_id,
                    'status' => $status,
                    'approved_revision' => $approval->approved_revision,
                ],
            );

            return $approval;
        });
    }

    /**
     * Issue a short-lived S3 pre-signed download URL for a ModVersion in a ConvoyProfile.
     *
     * @return array{download_url: string, expires_at: string, sha256_hash: string|null, file_size_bytes: int|null, archive_filename: string}
     */
    public function issueDownloadUrl(ConvoyProfile $profile, ModVersion $modVersion, User $user, ?Device $device = null): array
    {
        $isPinnedInProfile = $profile->profileMods()
            ->where('mod_version_id', $modVersion->id)
            ->exists();

        if (! $isPinnedInProfile) {
            throw ValidationException::withMessages([
                'mod_version_id' => ['This mod version is not part of the selected convoy profile.'],
            ]);
        }

        if (! $this->hasValidApproval($profile, $user)) {
            throw new AccessDeniedHttpException('Synchronisation approval is required before downloading mods for this convoy profile.');
        }

        $modVersion->loadMissing('source');
        $source = $modVersion->source;

        if ($source === null || $source->source_type !== ModSourceType::S3Upload || empty($source->s3_object_key)) {
            throw ValidationException::withMessages([
                'mod_version_id' => ['This mod version is hosted on Steam Workshop and does not have an S3 download URL.'],
            ]);
        }

        $signed = $this->s3Storage->createTemporaryDownloadUrl(
            objectKey: $source->s3_object_key,
            archiveFilename: $modVersion->archive_filename,
            minutes: 15,
            disk: $source->s3_disk ?? 's3',
        );

        $this->auditLogger->log(
            event: 'mod_version.download_url_issued',
            auditable: $modVersion,
            actor: $user,
            groupId: $profile->group_id,
            newValues: [
                'convoy_profile_id' => $profile->id,
                'archive_filename' => $modVersion->archive_filename,
            ],
            device: $device,
        );

        return [
            'download_url' => $signed['download_url'],
            'expires_at' => $signed['expires_at'],
            'sha256_hash' => $modVersion->sha256_hash,
            'file_size_bytes' => $modVersion->file_size_bytes,
            'archive_filename' => $modVersion->archive_filename,
        ];
    }

    /**
     * Record a desktop client's synchronisation session result for a ConvoyProfile (append-only history + per-item outcomes).
     *
     * @param  array{
     *     reported_manifest_hash?: string|null,
     *     status: SyncStatus|string,
     *     missing_mods_count?: int,
     *     mismatched_mods_count?: int,
     *     extra_local_mods_count?: int,
     *     bytes_downloaded?: int,
     *     duration_ms?: int|null,
     *     error_message?: string|null,
     *     items?: list<array{
     *         mod_version_id: int,
     *         action_taken: SyncItemAction|string,
     *         status: string,
     *         local_sha256_hash?: string|null,
     *         bytes_downloaded?: int,
     *         error_detail?: string|null
     *     }>
     * }  $data
     */
    public function reportSyncSession(
        ConvoyProfile $profile,
        User $user,
        Device $device,
        array $data,
    ): SyncSession {
        return DB::transaction(function () use ($profile, $user, $device, $data): SyncSession {
            $status = $data['status'] instanceof SyncStatus
                ? $data['status']
                : SyncStatus::from((string) $data['status']);

            $reportedHash = ! empty($data['reported_manifest_hash'])
                ? strtolower((string) $data['reported_manifest_hash'])
                : null;

            // If client claims in_sync, verify the reported manifest hash matches the profile's current manifest_hash
            if ($status === SyncStatus::InSync && $reportedHash !== null && $reportedHash !== $profile->manifest_hash) {
                $status = SyncStatus::OutOfSync;
            }

            $session = SyncSession::query()->create([
                'convoy_profile_id' => $profile->id,
                'convoy_profile_revision_id' => $profile->current_revision_id,
                'user_id' => $user->id,
                'device_id' => $device->id,
                'profile_revision' => $profile->revision,
                'reported_manifest_hash' => $reportedHash,
                'status' => $status,
                'missing_mods_count' => (int) ($data['missing_mods_count'] ?? 0),
                'mismatched_mods_count' => (int) ($data['mismatched_mods_count'] ?? 0),
                'extra_local_mods_count' => (int) ($data['extra_local_mods_count'] ?? 0),
                'bytes_downloaded' => (int) ($data['bytes_downloaded'] ?? 0),
                'duration_ms' => isset($data['duration_ms']) ? (int) $data['duration_ms'] : null,
                'error_message' => $data['error_message'] ?? null,
                'started_at' => now(),
                'synced_at' => $status === SyncStatus::InSync ? now() : null,
            ]);

            if (! empty($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $item) {
                    $action = $item['action_taken'] instanceof SyncItemAction
                        ? $item['action_taken']
                        : SyncItemAction::from((string) $item['action_taken']);

                    SyncSessionItem::query()->create([
                        'sync_session_id' => $session->id,
                        'mod_version_id' => (int) $item['mod_version_id'],
                        'action_taken' => $action,
                        'status' => (string) $item['status'],
                        'local_sha256_hash' => ! empty($item['local_sha256_hash']) ? strtolower((string) $item['local_sha256_hash']) : null,
                        'bytes_downloaded' => (int) ($item['bytes_downloaded'] ?? 0),
                        'error_detail' => $item['error_detail'] ?? null,
                    ]);
                }
            }

            $device->update(['last_seen_at' => now()]);

            $this->auditLogger->log(
                event: 'sync_session.reported',
                auditable: $session,
                actor: $user,
                groupId: $profile->group_id,
                newValues: [
                    'status' => $session->status->value,
                    'profile_revision' => $session->profile_revision,
                    'convoy_profile_revision_id' => $session->convoy_profile_revision_id,
                    'reported_manifest_hash' => $session->reported_manifest_hash,
                ],
                device: $device,
            );

            return $session->load('items');
        });
    }
}
