<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ReportSyncSessionRequest;
use App\Http\Resources\V1\ConvoyProfileManifestResource;
use App\Models\ConvoyProfile;
use App\Models\Device;
use App\Models\Group;
use App\Models\ModVersion;
use App\Models\User;
use App\Services\Sync\SyncCoordinatorService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class ConvoySyncApiController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly SyncCoordinatorService $syncService,
    ) {}

    /**
     * List all active ConvoyProfiles for a group.
     */
    public function indexProfiles(Request $request, Group $group): AnonymousResourceCollection
    {
        $this->authorize('view', $group);

        $profiles = $group->convoyProfiles()
            ->where('is_active', true)
            ->with(['group', 'game', 'profileMods.modVersion.mod', 'profileMods.modVersion.source', 'profileMods.modVersion.dependencies.relatedMod'])
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        return ConvoyProfileManifestResource::collection($profiles);
    }

    /**
     * Return the full deterministic manifest for a specific ConvoyProfile.
     */
    public function showManifest(Request $request, Group $group, ConvoyProfile $convoyProfile): ConvoyProfileManifestResource
    {
        $this->authorize('view', $convoyProfile);

        return new ConvoyProfileManifestResource($convoyProfile);
    }

    /**
     * Request synchronisation approval for an approval-gated ConvoyProfile.
     */
    public function requestSyncApproval(Request $request, ConvoyProfile $convoyProfile): JsonResponse
    {
        $this->authorize('view', $convoyProfile);

        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'device_uuid' => ['nullable', 'uuid'],
        ]);

        $device = ! empty($validated['device_uuid'])
            ? Device::query()->where('user_id', $user->id)->where('device_uuid', $validated['device_uuid'])->first()
            : null;

        $approval = $this->syncService->requestSyncApproval($convoyProfile, $user, $device);

        return response()->json([
            'approval_id' => $approval->id,
            'convoy_profile_id' => $convoyProfile->id,
            'approved_revision' => $approval->approved_revision,
            'status' => $approval->status,
        ], 202);
    }

    /**
     * Issue a short-lived S3 pre-signed download URL for a ModVersion in a ConvoyProfile.
     */
    public function issueDownloadUrl(
        Request $request,
        ConvoyProfile $convoyProfile,
        ModVersion $modVersion,
    ): JsonResponse {
        $this->authorize('view', $convoyProfile);

        /** @var User $user */
        $user = $request->user();

        $downloadData = $this->syncService->issueDownloadUrl($convoyProfile, $modVersion, $user);

        return response()->json($downloadData);
    }

    /**
     * Report the desktop client's synchronisation status for a ConvoyProfile.
     */
    public function reportSyncSession(
        ReportSyncSessionRequest $request,
        ConvoyProfile $convoyProfile,
    ): JsonResponse {
        $this->authorize('view', $convoyProfile);

        /** @var User $user */
        $user = $request->user();
        $validated = $request->validated();

        /** @var Device|null $boundDevice */
        $boundDevice = $request->attributes->get('authenticated_device');

        if ($boundDevice !== null && $boundDevice->device_uuid !== $validated['device_uuid']) {
            throw ValidationException::withMessages([
                'device_uuid' => ['The provided device_uuid does not match the device bound to your current API token.'],
            ]);
        }

        /** @var Device|null $device */
        $device = $boundDevice ?? Device::query()
            ->where('user_id', $user->id)
            ->where('device_uuid', $validated['device_uuid'])
            ->whereNull('revoked_at')
            ->first();

        if ($device === null) {
            throw ValidationException::withMessages([
                'device_uuid' => ['Active registered device not found for this user.'],
            ]);
        }

        $session = $this->syncService->reportSyncSession($convoyProfile, $user, $device, $validated);

        return response()->json([
            'session_id' => $session->id,
            'convoy_profile_id' => $session->convoy_profile_id,
            'profile_revision' => $session->profile_revision,
            'status' => $session->status->value,
            'synced_at' => $session->synced_at?->toIso8601ZuluString(),
        ]);
    }
}
