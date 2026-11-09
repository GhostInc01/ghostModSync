<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\ConvoySyncApiController;
use App\Http\Controllers\Api\V1\DeviceAuthController;
use App\Http\Controllers\Api\V1\GroupApiController;
use App\Http\Resources\V1\DeviceResource;
use App\Http\Resources\V1\UserResource;
use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::post('/auth/device-token', [DeviceAuthController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('auth.device-token.store');

    Route::middleware(['auth:sanctum', 'active.device', 'verified', 'throttle:api'])->group(function () {
        Route::delete('/auth/device-token', [DeviceAuthController::class, 'destroy'])
            ->name('auth.device-token.destroy');

        Route::get('/user', function (Request $request) {
            $user = $request->user();
            $currentToken = $user->currentAccessToken();

            /** @var Device|null $device */
            $device = $request->attributes->get('authenticated_device') ?? (
                $currentToken
                    ? Device::query()
                        ->where('user_id', $user->id)
                        ->where('personal_access_token_id', $currentToken->id)
                        ->first()
                    : null
            );

            return response()->json([
                'status' => 'ok',
                'user' => new UserResource($user),
                'device' => $device ? new DeviceResource($device) : null,
            ]);
        })->name('user.show');

        Route::get('/devices', [DeviceAuthController::class, 'indexDevices'])
            ->name('devices.index');
        Route::post('/devices', [DeviceAuthController::class, 'storeDevice'])
            ->name('devices.store');
        Route::get('/devices/{device}', [DeviceAuthController::class, 'showDevice'])
            ->name('devices.show');
        Route::patch('/devices/{device}', [DeviceAuthController::class, 'updateDevice'])
            ->name('devices.update');
        Route::post('/devices/{device}/heartbeat', [DeviceAuthController::class, 'heartbeat'])
            ->name('devices.heartbeat');
        Route::delete('/devices/{device}', [DeviceAuthController::class, 'revokeDevice'])
            ->name('devices.destroy');

        Route::middleware('ability:groups:read')->group(function () {
            Route::get('/groups', [GroupApiController::class, 'index'])->name('groups.index');
            Route::get('/groups/{group}', [GroupApiController::class, 'show'])->name('groups.show');
        });

        Route::scopeBindings()->middleware('ability:profiles:read')->group(function () {
            Route::get('/groups/{group}/profiles', [ConvoySyncApiController::class, 'indexProfiles'])
                ->name('groups.profiles.index');
            Route::get('/groups/{group}/profiles/{convoyProfile}/manifest', [ConvoySyncApiController::class, 'showManifest'])
                ->name('groups.profiles.manifest');
        });

        Route::post('/profiles/{convoyProfile:id}/sync-requests', [ConvoySyncApiController::class, 'requestSyncApproval'])
            ->middleware('ability:sync:report')
            ->name('profiles.sync-requests.store');
        Route::post('/profiles/{convoyProfile:id}/mods/{modVersion:id}/download-url', [ConvoySyncApiController::class, 'issueDownloadUrl'])
            ->middleware(['ability:mods:download', 'throttle:downloads'])
            ->name('profiles.mods.download-url');
        Route::post('/profiles/{convoyProfile:id}/sync-sessions', [ConvoySyncApiController::class, 'reportSyncSession'])
            ->middleware('ability:sync:report')
            ->name('profiles.sync-sessions.store');
    });
});
