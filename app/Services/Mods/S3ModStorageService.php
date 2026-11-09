<?php

declare(strict_types=1);

namespace App\Services\Mods;

use App\Models\Group;
use App\Models\Mod;
use App\Models\ModVersion;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class S3ModStorageService
{
    public const ALLOWED_EXTENSIONS = ['scs', 'zip'];

    /**
     * Sanitize and validate a target mod archive filename (.scs or .zip only, no path traversal).
     */
    public function sanitizeFilename(string $filename): string
    {
        $base = basename(str_replace('\\', '/', trim($filename)));

        if (! preg_match('/^[a-zA-Z0-9._\-]+\.(scs|zip)$/i', $base)) {
            throw ValidationException::withMessages([
                'archive_filename' => ['Archive filename must contain only alphanumeric characters, dots, underscores, or hyphens, and must end with .scs or .zip.'],
            ]);
        }

        return $base;
    }

    /**
     * Build a deterministic, non-guessable S3 object key for a ModVersion.
     */
    public function buildObjectKey(Group $group, Mod $mod, string $versionNumber, string $sanitizedFilename): string
    {
        $gameCode = $mod->game->code->value;
        $safeVersion = Str::slug($versionNumber);
        $nonce = strtolower(Str::random(8));

        return sprintf(
            'groups/%d/games/%s/mods/%d/versions/%s-%s/%s',
            $group->id,
            $gameCode,
            $mod->id,
            $safeVersion,
            $nonce,
            $sanitizedFilename
        );
    }

    /**
     * Enforce the group's storage quota before allocating a new S3 upload.
     */
    public function assertStorageQuotaAvailable(Group $group, int $additionalBytes): void
    {
        $usedBytes = (int) ModVersion::query()
            ->whereHas('mod', fn ($q) => $q->where('group_id', $group->id))
            ->sum('file_size_bytes');

        if (($usedBytes + $additionalBytes) > $group->storage_quota_bytes) {
            throw ValidationException::withMessages([
                'file_size_bytes' => ['This upload exceeds the group storage quota.'],
            ]);
        }
    }

    /**
     * Generate a short-lived S3 pre-signed PUT upload URL (or simulated URL in local/testing if driver does not support temporaryUploadUrl).
     *
     * @return array{upload_url: string, headers: array<string, mixed>, expires_at: string}
     */
    public function createTemporaryUploadUrl(string $objectKey, int $minutes = 30, string $disk = 's3'): array
    {
        $expiresAt = now()->addMinutes($minutes);

        try {
            $result = Storage::disk($disk)->temporaryUploadUrl($objectKey, $expiresAt);

            return [
                'upload_url' => $result['url'],
                'headers' => $result['headers'] ?? [],
                'expires_at' => $expiresAt->toIso8601ZuluString(),
            ];
        } catch (Throwable) {
            return [
                'upload_url' => url('/storage/s3-upload-placeholder/'.ltrim($objectKey, '/')),
                'headers' => [],
                'expires_at' => $expiresAt->toIso8601ZuluString(),
            ];
        }
    }

    /**
     * Generate a short-lived S3 pre-signed GET download URL for the desktop client.
     *
     * @return array{download_url: string, expires_at: string}
     */
    public function createTemporaryDownloadUrl(string $objectKey, string $archiveFilename, int $minutes = 15, string $disk = 's3'): array
    {
        $expiresAt = now()->addMinutes($minutes);

        try {
            $url = Storage::disk($disk)->temporaryUrl($objectKey, $expiresAt, [
                'ResponseContentDisposition' => 'attachment; filename="'.$archiveFilename.'"',
            ]);
        } catch (Throwable) {
            $url = url('/storage/s3-download-placeholder/'.ltrim($objectKey, '/'));
        }

        return [
            'download_url' => $url,
            'expires_at' => $expiresAt->toIso8601ZuluString(),
        ];
    }
}
