<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Admin\StoreGameVersionRequest;
use App\Http\Requests\Web\Admin\UpdateGameRequest;
use App\Models\Game;
use App\Models\User;
use App\Services\Admin\PlatformModerationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AdminSettingsController extends Controller
{
    public function __construct(
        private readonly PlatformModerationService $moderation,
    ) {}

    public function index(): View
    {
        Gate::authorize('access-admin-dashboard');

        $games = Game::query()
            ->with(['versions', 'dlcs'])
            ->orderBy('id')
            ->get();

        // Non-secret runtime metadata only — never expose APP_KEY, DB passwords, or AWS secret keys.
        $systemMetadata = [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'environment' => app()->environment(),
            'maintenance_mode' => app()->isDownForMaintenance(),
            'default_filesystem' => (string) config('filesystems.default', 'local'),
            's3_region' => (string) config('filesystems.disks.s3.region', 'not-configured'),
            's3_bucket' => (string) config('filesystems.disks.s3.bucket', 'not-configured'),
            'allowed_mod_extensions' => ['.scs', '.zip'],
            'checksum_algorithm' => 'SHA-256 (64-char hex)',
        ];

        return view('admin.settings.index', [
            'games' => $games,
            'systemMetadata' => $systemMetadata,
        ]);
    }

    public function storeGameVersion(StoreGameVersionRequest $request): RedirectResponse
    {
        Gate::authorize('manage-platform-settings');

        /** @var User $actor */
        $actor = $request->user()->fresh();

        $this->moderation->storeGameVersion($actor, $request->validated());

        return redirect()
            ->route('admin.settings.index')
            ->with('status', 'Game version branch saved.');
    }

    public function updateGame(UpdateGameRequest $request, Game $game): RedirectResponse
    {
        Gate::authorize('manage-platform-settings');

        /** @var User $actor */
        $actor = $request->user()->fresh();

        $this->moderation->updateGameStatus($actor, $game, (bool) $request->validated('is_active'));

        return redirect()
            ->route('admin.settings.index')
            ->with('status', 'Game status updated.');
    }
}
