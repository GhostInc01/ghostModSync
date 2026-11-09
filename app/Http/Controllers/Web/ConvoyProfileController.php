<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\StoreConvoyProfileRequest;
use App\Http\Requests\Web\SyncConvoyProfileModsRequest;
use App\Models\ConvoyProfile;
use App\Models\Group;
use App\Models\SyncApproval;
use App\Models\User;
use App\Services\Profiles\ConvoyProfileService;
use App\Services\Sync\SyncCoordinatorService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ConvoyProfileController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly ConvoyProfileService $profileService,
        private readonly SyncCoordinatorService $syncService,
    ) {}

    public function store(StoreConvoyProfileRequest $request, Group $group): RedirectResponse
    {
        $this->authorize('manageConvoyProfiles', $group);

        /** @var User $user */
        $user = $request->user();

        $this->profileService->createProfile($group, $user, $request->validated());

        return redirect()
            ->route('groups.show', $group)
            ->with('status', 'convoy-profile-created');
    }

    public function syncMods(
        SyncConvoyProfileModsRequest $request,
        Group $group,
        ConvoyProfile $convoyProfile,
    ): RedirectResponse {
        $this->authorize('update', $convoyProfile);

        /** @var User $user */
        $user = $request->user();

        $this->profileService->syncProfileMods(
            profile: $convoyProfile,
            actor: $user,
            orderedModItems: $request->validated('mods', []),
        );

        return redirect()
            ->route('groups.show', $group)
            ->with('status', 'convoy-profile-mods-synced');
    }

    public function reviewSyncApproval(
        Request $request,
        Group $group,
        ConvoyProfile $convoyProfile,
        SyncApproval $syncApproval,
    ): RedirectResponse {
        $this->authorize('approveSync', $convoyProfile);

        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:approved,rejected'],
        ]);

        $this->syncService->reviewSyncApproval($syncApproval, $user, (string) $validated['status']);

        return redirect()
            ->route('groups.show', $group)
            ->with('status', 'sync-approval-reviewed');
    }
}
