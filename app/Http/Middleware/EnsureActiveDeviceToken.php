<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Device;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveDeviceToken
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = $request->user();
        $currentToken = $user?->currentAccessToken();

        if ($user !== null && $currentToken instanceof PersonalAccessToken) {
            /** @var Device|null $device */
            $device = Device::query()
                ->where('user_id', $user->id)
                ->where('personal_access_token_id', $currentToken->id)
                ->first();

            if ($device !== null) {
                if ($device->isRevoked()) {
                    $currentToken->delete();

                    return response()->json([
                        'status' => 'error',
                        'error_code' => 'device_revoked',
                        'message' => 'This desktop device has been revoked. Please sign in again.',
                    ], 401);
                }

                if ($device->last_seen_at === null || $device->last_seen_at->lt(now()->subMinute())) {
                    $device->update([
                        'last_seen_at' => now(),
                    ]);
                }

                $request->attributes->set('authenticated_device', $device);
            }
        }

        return $next($request);
    }
}
