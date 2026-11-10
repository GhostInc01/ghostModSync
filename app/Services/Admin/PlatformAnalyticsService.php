<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Enums\GroupVisibility;
use App\Enums\MembershipStatus;
use App\Enums\ModApprovalStatus;
use App\Enums\ModerationReportStatus;
use App\Enums\ModSourceType;
use App\Enums\ModVersionStatus;
use App\Enums\SyncStatus;
use App\Models\AuditLog;
use App\Models\ConvoyProfile;
use App\Models\ConvoyProfileRevision;
use App\Models\Device;
use App\Models\Group;
use App\Models\GroupModApproval;
use App\Models\Mod;
use App\Models\ModerationReport;
use App\Models\ModVersion;
use App\Models\SyncApproval;
use App\Models\SyncSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PlatformAnalyticsService
{
    /**
     * Platform-wide overview statistics derived strictly from real database records.
     *
     * @return array<string, mixed>
     */
    public function overview(): array
    {
        $sevenDaysAgo = now()->subDays(7);

        $activeUserIds = User::query()
            ->where('last_login_at', '>=', $sevenDaysAgo)
            ->pluck('id')
            ->merge(Device::query()->where('last_seen_at', '>=', $sevenDaysAgo)->pluck('user_id'))
            ->merge(SyncSession::query()->where('updated_at', '>=', $sevenDaysAgo)->pluck('user_id'))
            ->unique();

        $totalS3Bytes = (int) ModVersion::query()
            ->whereHas('mod', fn ($q) => $q->where('source_type', ModSourceType::S3Upload->value))
            ->whereIn('status', [ModVersionStatus::Ready->value, ModVersionStatus::Deprecated->value])
            ->sum('file_size_bytes');

        $totalQuotaBytes = (int) Group::query()->sum('storage_quota_bytes');

        return [
            'users' => [
                'total' => User::query()->count(),
                'verified' => User::query()->whereNotNull('email_verified_at')->count(),
                'active_7d' => $activeUserIds->count(),
                'suspended' => User::query()->whereNotNull('suspended_at')->count(),
                'platform_admins' => User::query()
                    ->where(fn ($q) => $q->where('is_platform_admin', true)->orWhereNotNull('platform_role'))
                    ->count(),
            ],
            'groups' => [
                'total' => Group::query()->count(),
                'public' => Group::query()->where('visibility', GroupVisibility::Public->value)->count(),
                'private' => Group::query()->where('visibility', GroupVisibility::Private->value)->count(),
                'archived' => Group::onlyTrashed()->count(),
            ],
            'mods' => [
                'total' => Mod::query()->count(),
                'ready_versions' => ModVersion::query()->where('status', ModVersionStatus::Ready->value)->count(),
                'pending_versions' => ModVersion::query()->where('status', ModVersionStatus::Pending->value)->count(),
                'approved_mods' => GroupModApproval::query()->where('status', ModApprovalStatus::Approved->value)->count(),
                'pending_approvals' => GroupModApproval::query()->where('status', ModApprovalStatus::Pending->value)->count(),
            ],
            'convoys' => [
                'total' => ConvoyProfile::query()->count(),
                'published' => ConvoyProfile::query()->where('is_active', true)->count(),
                'revisions' => ConvoyProfileRevision::query()->count(),
            ],
            'sync' => [
                'total_sessions' => SyncSession::query()->count(),
                'in_sync' => SyncSession::query()->where('status', SyncStatus::InSync->value)->count(),
                'failed' => SyncSession::query()->where('status', SyncStatus::Failed->value)->count(),
                'pending_approvals' => SyncApproval::query()->where('status', 'pending')->count(),
                'bytes_downloaded' => (int) SyncSession::query()->sum('bytes_downloaded'),
                'active_devices' => Device::query()->whereNull('revoked_at')->count(),
            ],
            'storage' => [
                'used_bytes' => $totalS3Bytes,
                'quota_bytes' => $totalQuotaBytes,
                'pending_uploads' => ModVersion::query()->where('status', ModVersionStatus::Pending->value)->count(),
            ],
            'moderation' => [
                'pending_reports' => ModerationReport::query()->where('status', ModerationReportStatus::Pending->value)->count(),
            ],
            'recentAuditLogs' => AuditLog::query()
                ->with(['user', 'device'])
                ->select('id', 'group_id', 'user_id', 'device_id', 'event', 'auditable_type', 'auditable_id', 'created_at')
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->limit(8)
                ->get(),
            'recentFailedSyncs' => SyncSession::query()
                ->where('status', SyncStatus::Failed->value)
                ->with(['user', 'device', 'convoyProfile.group'])
                ->latest('updated_at')
                ->limit(5)
                ->get(),
        ];
    }

    /**
     * Storage consumption breakdown by group, largest archives, duplicate SHA-256 hashes, and pending/revoked uploads.
     *
     * @return array<string, mixed>
     */
    public function storageBreakdown(): array
    {
        $usedByGroup = DB::table('mod_versions')
            ->join('mods', 'mods.id', '=', 'mod_versions.mod_id')
            ->whereNull('mod_versions.deleted_at')
            ->whereNull('mods.deleted_at')
            ->where('mods.source_type', ModSourceType::S3Upload->value)
            ->whereIn('mod_versions.status', [ModVersionStatus::Ready->value, ModVersionStatus::Deprecated->value])
            ->selectRaw('mods.group_id, COALESCE(SUM(mod_versions.file_size_bytes), 0) as used_bytes, COUNT(mod_versions.id) as version_count')
            ->groupBy('mods.group_id')
            ->get()
            ->keyBy('group_id');

        $groups = Group::withTrashed()
            ->with('owner')
            ->orderBy('name')
            ->get()
            ->each(function (Group $group) use ($usedByGroup): void {
                $row = $usedByGroup->get($group->id);
                $used = (int) ($row->used_bytes ?? 0);
                $quota = max(1, (int) $group->storage_quota_bytes);
                $group->setAttribute('used_storage_bytes', $used);
                $group->setAttribute('s3_version_count', (int) ($row->version_count ?? 0));
                $group->setAttribute('quota_usage_percent', round(($used / $quota) * 100, 1));
            });

        $duplicateHashes = DB::table('mod_versions')
            ->whereNull('deleted_at')
            ->whereNotNull('sha256_hash')
            ->selectRaw('sha256_hash, COUNT(*) as occurrences, COALESCE(SUM(file_size_bytes), 0) as total_bytes')
            ->groupBy('sha256_hash')
            ->havingRaw('COUNT(*) > 1')
            ->orderByDesc('occurrences')
            ->limit(20)
            ->get();

        $largestVersions = ModVersion::query()
            ->whereNotNull('file_size_bytes')
            ->with(['mod.group', 'uploader'])
            ->orderByDesc('file_size_bytes')
            ->limit(15)
            ->get();

        $problematicUploads = ModVersion::query()
            ->whereIn('status', [ModVersionStatus::Pending->value, ModVersionStatus::Revoked->value])
            ->with(['mod.group', 'uploader'])
            ->latest()
            ->limit(15)
            ->get();

        return [
            'totalUsedBytes' => (int) $groups->sum('used_storage_bytes'),
            'totalQuotaBytes' => (int) $groups->sum('storage_quota_bytes'),
            'groups' => $groups,
            'duplicateHashes' => $duplicateHashes,
            'largestVersions' => $largestVersions,
            'problematicUploads' => $problematicUploads,
        ];
    }

    /**
     * Real platform reporting metrics across users, groups, mods, and synchronisation.
     *
     * @return array<string, mixed>
     */
    public function reports(): array
    {
        $topGroups = Group::query()
            ->with('owner')
            ->withCount([
                'memberships as active_members_count' => fn ($q) => $q->where('status', MembershipStatus::Active->value),
                'mods',
                'convoyProfiles',
            ])
            ->orderByDesc('active_members_count')
            ->limit(10)
            ->get();

        $syncByStatus = SyncSession::query()
            ->selectRaw('status, COUNT(*) as session_count, COALESCE(SUM(bytes_downloaded), 0) as total_bytes, AVG(duration_ms) as avg_duration_ms')
            ->groupBy('status')
            ->get()
            ->keyBy(fn ($row) => $row->status instanceof SyncStatus ? $row->status->value : (string) $row->status);

        $modsBySource = Mod::query()
            ->selectRaw('source_type, COUNT(*) as mod_count')
            ->groupBy('source_type')
            ->pluck('mod_count', 'source_type');

        return [
            'userSummary' => [
                'total' => User::query()->count(),
                'verified' => User::query()->whereNotNull('email_verified_at')->count(),
                'unverified' => User::query()->whereNull('email_verified_at')->count(),
                'suspended' => User::query()->whereNotNull('suspended_at')->count(),
                'registered_last_30d' => User::query()->where('created_at', '>=', now()->subDays(30))->count(),
            ],
            'topGroups' => $topGroups,
            'modsBySource' => [
                's3_upload' => (int) ($modsBySource[ModSourceType::S3Upload->value] ?? 0),
                'steam_workshop' => (int) ($modsBySource[ModSourceType::SteamWorkshop->value] ?? 0),
            ],
            'syncByStatus' => $syncByStatus,
        ];
    }

    /**
     * Stream a CSV export of non-sensitive platform report data.
     */
    public function exportCsv(string $type): StreamedResponse
    {
        $filename = 'ghostmodsync-'.$type.'-report-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($type): void {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }

            match ($type) {
                'users' => $this->writeUsersCsv($out),
                'groups' => $this->writeGroupsCsv($out),
                'mods' => $this->writeModsCsv($out),
                'sync' => $this->writeSyncCsv($out),
                default => fputcsv($out, ['Invalid report type']),
            };

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Sanitise CSV row values against spreadsheet formula injection (=, +, -, @, tab, CR).
     *
     * @param  list<scalar|null>  $row
     * @return list<scalar|null>
     */
    private function sanitizeCsvRow(array $row): array
    {
        return array_map(static function (mixed $cell): mixed {
            if (! is_string($cell) || $cell === '') {
                return $cell;
            }

            if (in_array($cell[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
                return "'".$cell;
            }

            return $cell;
        }, $row);
    }

    /**
     * @param  resource  $out
     */
    private function writeUsersCsv($out): void
    {
        fputcsv($out, ['ID', 'Name', 'Username', 'Email', 'Verified', 'Platform Role', 'Suspended', 'Created At']);
        User::query()->orderBy('id')->chunk(200, function ($users) use ($out): void {
            foreach ($users as $u) {
                fputcsv($out, $this->sanitizeCsvRow([
                    $u->id,
                    $u->name,
                    $u->username ?? '',
                    $u->email,
                    $u->email_verified_at ? 'yes' : 'no',
                    $u->platformRoleLabel(),
                    $u->isSuspended() ? 'yes' : 'no',
                    $u->created_at?->toIso8601String() ?? '',
                ]));
            }
        });
    }

    /**
     * @param  resource  $out
     */
    private function writeGroupsCsv($out): void
    {
        fputcsv($out, ['ID', 'Name', 'Slug', 'Tag', 'Visibility', 'Sync Policy', 'Quota Bytes', 'Status', 'Created At']);
        Group::withTrashed()->orderBy('id')->chunk(200, function ($groups) use ($out): void {
            foreach ($groups as $g) {
                fputcsv($out, $this->sanitizeCsvRow([
                    $g->id,
                    $g->name,
                    $g->slug,
                    $g->tag ?? '',
                    $g->visibility->value,
                    $g->sync_policy->value,
                    $g->storage_quota_bytes,
                    $g->trashed() ? 'archived' : 'active',
                    $g->created_at?->toIso8601String() ?? '',
                ]));
            }
        });
    }

    /**
     * @param  resource  $out
     */
    private function writeModsCsv($out): void
    {
        fputcsv($out, ['ID', 'Name', 'Group', 'Game', 'Source Type', 'Active', 'Created At']);
        Mod::withTrashed()->with(['group', 'game'])->orderBy('id')->chunk(200, function ($mods) use ($out): void {
            foreach ($mods as $m) {
                fputcsv($out, $this->sanitizeCsvRow([
                    $m->id,
                    $m->name,
                    $m->group?->name ?? '',
                    $m->game?->code?->value ?? '',
                    $m->source_type->value,
                    $m->is_active ? 'yes' : 'no',
                    $m->created_at?->toIso8601String() ?? '',
                ]));
            }
        });
    }

    /**
     * @param  resource  $out
     */
    private function writeSyncCsv($out): void
    {
        fputcsv($out, ['Session ID', 'Profile', 'User Email', 'Device', 'Revision', 'Status', 'Bytes Downloaded', 'Duration ms', 'Updated At']);
        SyncSession::query()->with(['convoyProfile', 'user', 'device'])->orderBy('id')->chunk(200, function ($sessions) use ($out): void {
            foreach ($sessions as $s) {
                fputcsv($out, $this->sanitizeCsvRow([
                    $s->id,
                    $s->convoyProfile?->name ?? '',
                    $s->user?->email ?? '',
                    $s->device?->device_name ?? '',
                    $s->profile_revision,
                    $s->status->value,
                    $s->bytes_downloaded,
                    $s->duration_ms ?? 0,
                    $s->updated_at?->toIso8601String() ?? '',
                ]));
            }
        });
    }
}
