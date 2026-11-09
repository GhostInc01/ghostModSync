<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\DlcCategory;
use App\Enums\GameCode;
use App\Models\Game;
use App\Models\GameDlc;
use Illuminate\Database\Seeder;

class GameSeeder extends Seeder
{
    public function run(): void
    {
        foreach (GameCode::cases() as $gameCode) {
            $game = Game::query()->updateOrCreate(
                ['code' => $gameCode->value],
                [
                    'name' => $gameCode->label(),
                    'steam_app_id' => $gameCode->steamAppId(),
                    'default_mod_dir_name' => $gameCode->defaultModDirName(),
                    'is_active' => true,
                ]
            );

            $branches = ['1.51', '1.52', '1.53'];
            foreach ($branches as $index => $branch) {
                $game->versions()->updateOrCreate(
                    ['version_branch' => $branch],
                    [
                        'is_current' => $branch === '1.53',
                        'released_at' => now()->subMonths(count($branches) - 1 - $index)->toDateString(),
                    ]
                );
            }

            $dlcs = $gameCode === GameCode::Ets2
                ? [
                    ['code' => 'dlc_east', 'name' => 'Going East!', 'steam_app_id' => 227310, 'category' => DlcCategory::Map],
                    ['code' => 'dlc_north', 'name' => 'Scandinavia', 'steam_app_id' => 304212, 'category' => DlcCategory::Map],
                    ['code' => 'dlc_balkan_w', 'name' => 'West Balkans', 'steam_app_id' => 2004210, 'category' => DlcCategory::Map],
                    ['code' => 'dlc_heavy_cargo', 'name' => 'Heavy Cargo Pack', 'steam_app_id' => 531131, 'category' => DlcCategory::Cargo],
                ]
                : [
                    ['code' => 'dlc_texas', 'name' => 'Texas', 'steam_app_id' => 1465750, 'category' => DlcCategory::Map],
                    ['code' => 'dlc_heavy_cargo_ats', 'name' => 'Heavy Cargo Pack (ATS)', 'steam_app_id' => 620610, 'category' => DlcCategory::Cargo],
                ];

            foreach ($dlcs as $dlc) {
                GameDlc::query()->updateOrCreate(
                    [
                        'game_id' => $game->id,
                        'code' => $dlc['code'],
                    ],
                    [
                        'name' => $dlc['name'],
                        'steam_app_id' => $dlc['steam_app_id'],
                        'category' => $dlc['category'],
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
