<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Enums\ModApprovalStatus;
use App\Enums\ModerationReportStatus;
use App\Enums\ModVersionStatus;
use App\Enums\PlatformRole;
use App\Models\Game;
use App\Models\GameVersion;
use App\Models\Group;
use App\Models\GroupModApproval;
use App\Models\ModerationReport;
use App\Models\ModVersion;
use App\Models\User;
use App\Services\Audit\AuditLoggerService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PlatformModerationService
{
    public function __construct(
        private readonly AuditLoggerService $auditLogger,
    ) {}

    /**
     * Suspend or reactivate a user account globally, and optionally update their platform role (Super Admin only).
     *
     * @param  array{action: string, reason?: string|null, platform_role?: string|null}  $data
     */
    public function updateUserStatus(User $actor, User $target, array $data): User
    {
        if ($actor->id === $target->id) {
            throw ValidationException::withMessages([
                'action' => ['You cannot suspend or modify the platform role of your own account.'],
            ]);
        }

        return DB::transaction(function () use ($actor, $target, $data): User {
            $action = $data['action'];
            $oldValues = [
                'suspended_at' => $target->suspended_at?->toIso8601String(),
                'suspended_reason' => $target->suspended_reason,
                'is_platform_admin' => $target->is_platform_admin,
                'platform_role' => $target->platform_role?->value,
            ];

            if ($action === 'suspend') {
                if ($target->isSuperAdmin() && ! $actor->isSuperAdmin()) {
                    throw ValidationException::withMessages([
                        'action' => ['Only a Platform Super Admin can suspend another Super Admin.'],
                    ]);
                }

                $target->forceFill([
                    'suspended_at' => now(),
                    'suspended_reason' => $data['reason'] ?? 'Suspended by platform administrator.',
                ])->save();

                // Revoke active Sanctum tokens immediately.
                $target->tokens()->delete();

                $this->auditLogger->log(
                    event: 'admin.user.suspended',
                    auditable: $target,
                    actor: $actor,
                    oldValues: $oldValues,
                    newValues: [
                        'suspended_at' => $target->suspended_at?->toIso8601String(),
                        'suspended_reason' => $target->suspended_reason,
                    ],
                );
            } elseif ($action === 'reactivate') {
                $target->forceFill([
                    'suspended_at' => null,
                    'suspended_reason' => null,
                ])->save();

                $this->auditLogger->log(
                    event: 'admin.user.reactivated',
                    auditable: $target,
                    actor: $actor,
                    oldValues: $oldValues,
                    newValues: [
                        'suspended_at' => null,
                        'reason' => $data['reason'] ?? null,
                    ],
                );
            } elseif ($action === 'update_role') {
                if (! $actor->isSuperAdmin()) {
                    throw ValidationException::withMessages([
                        'platform_role' => ['Only a Platform Super Admin can assign or revoke platform administrator roles.'],
                    ]);
                }

                $newRole = ! empty($data['platform_role']) && $data['platform_role'] !== 'none'
                    ? PlatformRole::from((string) $data['platform_role'])
                    : null;

                $target->forceFill([
                    'is_platform_admin' => $newRole !== null,
                    'platform_role' => $newRole,
                ])->save();

                $this->auditLogger->log(
                    event: 'admin.user.role_updated',
                    auditable: $target,
                    actor: $actor,
                    oldValues: $oldValues,
                    newValues: [
                        'is_platform_admin' => $target->is_platform_admin,
                        'platform_role' => $target->platform_role?->value,
                    ],
                );
            }

            return $target->fresh();
        });
    }

    /**
     * Archive (soft-delete), restore, or update storage quota for a group.
     *
     * @param  array{action: string, storage_quota_gb?: int|null, reason?: string|null}  $data
     */
    public function updateGroup(User $actor, Group $group, array $data): Group
    {
        return DB::transaction(function () use ($actor, $group, $data): Group {
            $action = $data['action'];
            $oldValues = [
                'deleted_at' => $group->deleted_at?->toIso8601String(),
                'storage_quota_bytes' => $group->storage_quota_bytes,
            ];

            if ($action === 'archive' && ! $group->trashed()) {
                $group->delete();

                $this->auditLogger->log(
                    event: 'admin.group.archived',
                    auditable: $group,
                    actor: $actor,
                    groupId: $group->id,
                    oldValues: $oldValues,
                    newValues: ['deleted_at' => $group->deleted_at?->toIso8601String(), 'reason' => $data['reason'] ?? null],
                );
            } elseif ($action === 'restore' && $group->trashed()) {
                $group->restore();

                $this->auditLogger->log(
                    event: 'admin.group.restored',
                    auditable: $group,
                    actor: $actor,
                    groupId: $group->id,
                    oldValues: $oldValues,
                    newValues: ['deleted_at' => null, 'reason' => $data['reason'] ?? null],
                );
            } elseif ($action === 'update_quota' && isset($data['storage_quota_gb'])) {
                $quotaBytes = (int) $data['storage_quota_gb'] * 1073741824;
                $group->update(['storage_quota_bytes' => $quotaBytes]);

                $this->auditLogger->log(
                    event: 'admin.group.quota_updated',
                    auditable: $group,
                    actor: $actor,
                    groupId: $group->id,
                    oldValues: $oldValues,
                    newValues: ['storage_quota_bytes' => $quotaBytes],
                );
            }

            return $group->fresh() ?? $group;
        });
    }

    /**
     * Update a ModVersion status (ready, deprecated, revoked) and record an audit log entry.
     */
    public function updateModVersionStatus(User $actor, ModVersion $version, ModVersionStatus $status, ?string $reason = null): ModVersion
    {
        return DB::transaction(function () use ($actor, $version, $status, $reason): ModVersion {
            $version->loadMissing('mod');
            $oldStatus = $version->status->value;
            $version->update([
                'status' => $status,
                'published_at' => $status === ModVersionStatus::Ready && $version->published_at === null ? now() : $version->published_at,
            ]);

            $this->auditLogger->log(
                event: 'admin.mod_version.status_updated',
                auditable: $version,
                actor: $actor,
                groupId: $version->mod?->group_id,
                oldValues: ['status' => $oldStatus],
                newValues: ['status' => $status->value, 'reason' => $reason],
            );

            return $version->fresh();
        });
    }

    /**
     * Review a pending GroupModApproval record from the platform admin panel.
     */
    public function reviewGroupModApproval(User $actor, GroupModApproval $approval, ModApprovalStatus $status, ?string $notes = null): GroupModApproval
    {
        return DB::transaction(function () use ($actor, $approval, $status, $notes): GroupModApproval {
            $oldStatus = $approval->status->value;
            $approval->update([
                'status' => $status,
                'checksum_verified' => $status === ModApprovalStatus::Approved,
                'reviewed_by' => $actor->id,
                'review_notes' => $notes,
                'approved_at' => $status === ModApprovalStatus::Approved ? now() : $approval->approved_at,
                'revoked_at' => $status === ModApprovalStatus::Revoked ? now() : null,
            ]);

            $this->auditLogger->log(
                event: 'admin.group_mod_approval.'.$status->value,
                auditable: $approval,
                actor: $actor,
                groupId: $approval->group_id,
                oldValues: ['status' => $oldStatus],
                newValues: ['status' => $status->value, 'review_notes' => $notes],
            );

            return $approval->fresh();
        });
    }

    /**
     * Resolve or dismiss a moderation report on a group or mod.
     */
    public function reviewModerationReport(User $actor, ModerationReport $report, ModerationReportStatus $status, ?string $notes = null): ModerationReport
    {
        return DB::transaction(function () use ($actor, $report, $status, $notes): ModerationReport {
            $oldStatus = $report->status->value;
            $report->update([
                'status' => $status,
                'reviewed_by' => $actor->id,
                'review_notes' => $notes,
                'reviewed_at' => now(),
            ]);

            $this->auditLogger->log(
                event: 'admin.moderation_report.'.$status->value,
                auditable: $report,
                actor: $actor,
                oldValues: ['status' => $oldStatus],
                newValues: ['status' => $status->value, 'review_notes' => $notes],
            );

            return $report->fresh();
        });
    }

    /**
     * Register or update a supported game version branch (Super Admin only).
     *
     * @param  array{game_id: int, version_branch: string, is_current?: bool, released_at?: string|null}  $data
     */
    public function storeGameVersion(User $actor, array $data): GameVersion
    {
        return DB::transaction(function () use ($actor, $data): GameVersion {
            $isCurrent = (bool) ($data['is_current'] ?? false);

            if ($isCurrent) {
                GameVersion::query()
                    ->where('game_id', $data['game_id'])
                    ->update(['is_current' => false]);
            }

            $version = GameVersion::query()->updateOrCreate(
                [
                    'game_id' => (int) $data['game_id'],
                    'version_branch' => trim($data['version_branch']),
                ],
                [
                    'is_current' => $isCurrent,
                    'released_at' => $data['released_at'] ?? now()->toDateString(),
                ]
            );

            $this->auditLogger->log(
                event: 'admin.game_version.saved',
                auditable: $version,
                actor: $actor,
                newValues: [
                    'game_id' => $version->game_id,
                    'version_branch' => $version->version_branch,
                    'is_current' => $version->is_current,
                ],
            );

            return $version;
        });
    }

    /**
     * Toggle a supported game's active status (Super Admin only).
     */
    public function updateGameStatus(User $actor, Game $game, bool $isActive): Game
    {
        return DB::transaction(function () use ($actor, $game, $isActive): Game {
            $oldActive = $game->is_active;
            $game->update(['is_active' => $isActive]);

            $this->auditLogger->log(
                event: 'admin.game.updated',
                auditable: $game,
                actor: $actor,
                oldValues: ['is_active' => $oldActive],
                newValues: ['is_active' => $isActive],
            );

            return $game->fresh();
        });
    }
}
