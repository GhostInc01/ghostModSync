<?php

declare(strict_types=1);

namespace App\Services\Mods;

use Illuminate\Support\Facades\Http;
use Throwable;

class SteamWorkshopService
{
    public const STEAM_PUBLISHED_FILE_DETAILS_URL = 'https://api.steampowered.com/ISteamRemoteStorage/GetPublishedFileDetails/v1/';

    /**
     * Fetch metadata for a Steam Workshop item by its PublishedFileId.
     *
     * @return array{
     *     published_file_id: string,
     *     title: string|null,
     *     file_size_bytes: int|null,
     *     time_updated: int|null,
     *     workshop_url: string
     * }
     */
    public function fetchWorkshopItemDetails(string $publishedFileId): array
    {
        $workshopUrl = 'https://steamcommunity.com/sharedfiles/filedetails/?id='.$publishedFileId;

        try {
            $response = Http::asForm()
                ->timeout(8)
                ->post(self::STEAM_PUBLISHED_FILE_DETAILS_URL, [
                    'itemcount' => 1,
                    'publishedfileids[0]' => $publishedFileId,
                ]);

            if ($response->successful()) {
                $details = $response->json('response.publishedfiledetails.0');
                if (is_array($details) && ($details['result'] ?? 0) === 1) {
                    return [
                        'published_file_id' => $publishedFileId,
                        'title' => isset($details['title']) ? (string) $details['title'] : null,
                        'file_size_bytes' => isset($details['file_size']) && (int) $details['file_size'] > 0
                            ? (int) $details['file_size']
                            : null,
                        'time_updated' => isset($details['time_updated']) ? (int) $details['time_updated'] : null,
                        'workshop_url' => $workshopUrl,
                    ];
                }
            }
        } catch (Throwable) {
            // Fallback gracefully if Steam API is unreachable
        }

        return [
            'published_file_id' => $publishedFileId,
            'title' => null,
            'file_size_bytes' => null,
            'time_updated' => null,
            'workshop_url' => $workshopUrl,
        ];
    }
}
