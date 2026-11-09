<?php

declare(strict_types=1);

namespace App\Enums;

enum SyncItemAction: string
{
    case VerifiedLocal = 'verified_local';
    case DownloadedS3 = 'downloaded_s3';
    case VerifiedWorkshop = 'verified_workshop';
    case RemovedExtra = 'removed_extra';
    case SkippedOptional = 'skipped_optional';

    public function label(): string
    {
        return match ($this) {
            self::VerifiedLocal => 'Verified Local File',
            self::DownloadedS3 => 'Downloaded from S3',
            self::VerifiedWorkshop => 'Verified Steam Workshop',
            self::RemovedExtra => 'Removed Unlisted Mod',
            self::SkippedOptional => 'Skipped Optional Mod',
        };
    }
}
