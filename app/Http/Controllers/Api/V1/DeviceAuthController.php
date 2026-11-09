<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IssueDeviceTokenRequest;
use App\Http\Requests\Api\V1\RegisterDeviceRequest;
use App\Http\Requests\Api\V1\UpdateDeviceRequest;
use App\Http\Resources\V1\DeviceResource;
use App\Http\Resources\V1\UserResource;
use App\Models\Device;
use App\Models\User;
use App\Services\Devices\DeviceRegistrationService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class DeviceAuthController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly DeviceRegistrationService $deviceRegistrationService,
    ) {}

    /**
     * Authenticate a Windows desktop client and issue a device-bound Sanctum token.
     */
    public function store(IssueDeviceTokenRequest $request): JsonResponse
    {
        $validated = $request->validated();

        /** @var User|null $user */
        $user = User::query()->where('email', strtolower((string) $validated['email']))->first();

        if (! $user || ! Hash::check((string) $validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => [trans('auth.failed')],
            ]);
        }

        if (! $user->hasVerifiedEmail()) {
            return response()->json([
                'status' => 'error',
                'error_code' => 'email_unverified',
                'message' => 'Your email address is not verified. Please verify your email in the web portal before connecting the desktop app.',
            ], 403);
        }

        $result = $this->deviceRegistrationService->registerAndIssueToken(
            user: $user,
            attributes: $validated,
        );

        return response()->json([
            'status' => 'ok',
            'token' => $result['plain_text_token'],
            'abilities' => DeviceRegistrationService::DEFAULT_TOKEN_ABILITIES,
            'user' => new UserResource($user),
            'device' => new DeviceResource($result['device']),
        ], 201);
    }

    /**
     * Register a desktop device for an already-authenticated user.
     */
    public function storeDevice(RegisterDeviceRequest $request): JsonResponse
    {
        $this->authorize('create', Device::class);

        /** @var User $user */
        $user = $request->user();
        $currentToken = $user->currentAccessToken();
        $tokenId = $currentToken instanceof PersonalAccessToken ? $currentToken->id : null;

        $device = $this->deviceRegistrationService->registerDeviceForAuthenticatedUser(
            user: $user,
            attributes: $request->validated(),
            personalAccessTokenId: $tokenId,
        );

        return response()->json([
            'status' => 'ok',
            'device' => new DeviceResource($device),
        ], 201);
    }

    /**
     * List all registered desktop devices belonging to the authenticated user.
     */
    public function indexDevices(Request $request): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        $devices = $user->devices()
            ->orderByDesc('last_seen_at')
            ->get();

        return DeviceResource::collection($devices);
    }

    /**
     * View a single registered desktop device.
     */
    public function showDevice(Request $request, Device $device): JsonResponse
    {
        $this->authorize('view', $device);

        return response()->json([
            'status' => 'ok',
            'device' => new DeviceResource($device),
        ]);
    }

    /**
     * Update a registered desktop device's metadata.
     */
    public function updateDevice(UpdateDeviceRequest $request, Device $device): JsonResponse
    {
        $this->authorize('update', $device);

        /** @var User $user */
        $user = $request->user();

        $updated = $this->deviceRegistrationService->updateDevice($device, $user, $request->validated());

        return response()->json([
            'status' => 'ok',
            'device' => new DeviceResource($updated),
        ]);
    }

    /**
     * Update the last-seen heartbeat and optional client/OS version for a device.
     */
    public function heartbeat(UpdateDeviceRequest $request, Device $device): JsonResponse
    {
        $this->authorize('update', $device);

        if ($device->isRevoked()) {
            return response()->json([
                'status' => 'error',
                'error_code' => 'device_revoked',
                'message' => 'Cannot record heartbeat for a revoked device.',
            ], 403);
        }

        $updated = $this->deviceRegistrationService->recordHeartbeat($device, $request->validated());

        return response()->json([
            'status' => 'ok',
            'device' => new DeviceResource($updated),
        ]);
    }

    /**
     * Revoke or permanently remove a specific registered device and its associated Sanctum token.
     */
    public function revokeDevice(Request $request, Device $device): JsonResponse
    {
        $this->authorize('revoke', $device);

        /** @var User $user */
        $user = $request->user();

        if ($request->boolean('remove')) {
            $this->deviceRegistrationService->removeDevice($device, $user);

            return response()->json(null, 204);
        }

        $updated = $this->deviceRegistrationService->revokeDevice($device, $user);

        return response()->json([
            'status' => 'ok',
            'device' => new DeviceResource($updated),
        ]);
    }

    /**
     * Revoke the current desktop device token on logout.
     */
    public function destroy(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $currentToken = $user->currentAccessToken();

        if ($currentToken !== null) {
            /** @var Device|null $device */
            $device = Device::query()
                ->where('user_id', $user->id)
                ->where('personal_access_token_id', $currentToken->id)
                ->first();

            if ($device !== null) {
                $this->deviceRegistrationService->revokeDevice($device, $user);
            } else {
                $currentToken->delete();
            }
        }

        return response()->json(null, 204);
    }
}
