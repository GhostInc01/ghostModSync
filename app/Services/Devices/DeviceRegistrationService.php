<?php

declare(strict_types=1);

namespace App\Services\Devices;

use App\Models\Device;
use App\Models\User;
use App\Services\Audit\AuditLoggerService;
use Illuminate\Support\Facades\DB;

class DeviceRegistrationService
{
    /** @var list<string> */
    public const DEFAULT_TOKEN_ABILITIES = [
        'groups:read',
        'profiles:read',
        'mods:download',
        'sync:report',
    ];

    public function __construct(
        private readonly AuditLoggerService $auditLogger,
    ) {}

    /**
     * Register or refresh a Windows desktop client device and issue a device-bound Sanctum PAT.
     *
     * @param  array{
     *     device_uuid: string,
     *     device_name: string,
     *     os_version?: string|null,
     *     client_version?: string|null,
     *     ets2_mod_path?: string|null,
     *     ats_mod_path?: string|null
     * }  $attributes
     * @return array{device: Device, plain_text_token: string}
     */
    public function registerAndIssueToken(User $user, array $attributes): array
    {
        return DB::transaction(function () use ($user, $attributes): array {
            /** @var Device|null $existing */
            $existing = Device::query()
                ->where('user_id', $user->id)
                ->where('device_uuid', $attributes['device_uuid'])
                ->first();

            if ($existing?->personal_access_token_id !== null) {
                $user->tokens()->whereKey($existing->personal_access_token_id)->delete();
            }

            $newAccessToken = $user->createToken(
                name: 'desktop:'.$attributes['device_name'],
                abilities: self::DEFAULT_TOKEN_ABILITIES,
            );

            $device = Device::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'device_uuid' => $attributes['device_uuid'],
                ],
                [
                    'personal_access_token_id' => $newAccessToken->accessToken->id,
                    'device_name' => $attributes['device_name'],
                    'os_version' => $attributes['os_version'] ?? null,
                    'client_version' => $attributes['client_version'] ?? null,
                    'ets2_mod_path' => $attributes['ets2_mod_path'] ?? null,
                    'ats_mod_path' => $attributes['ats_mod_path'] ?? null,
                    'last_seen_at' => now(),
                    'revoked_at' => null,
                ]
            );

            $user->update([
                'last_login_at' => now(),
            ]);

            $this->auditLogger->log(
                event: 'device.authenticated',
                auditable: $device,
                actor: $user,
                newValues: [
                    'device_uuid' => $device->device_uuid,
                    'device_name' => $device->device_name,
                    'client_version' => $device->client_version,
                ],
                device: $device,
            );

            return [
                'device' => $device,
                'plain_text_token' => $newAccessToken->plainTextToken,
            ];
        });
    }

    /**
     * Revoke a registered device and immediately delete its bound Sanctum token.
     */
    public function revokeDevice(Device $device, User $actor): Device
    {
        return DB::transaction(function () use ($device, $actor): Device {
            if ($device->personal_access_token_id !== null) {
                $device->user->tokens()->whereKey($device->personal_access_token_id)->delete();
            }

            $device->update([
                'personal_access_token_id' => null,
                'revoked_at' => now(),
            ]);

            $this->auditLogger->log(
                event: 'device.revoked',
                auditable: $device,
                actor: $actor,
                newValues: [
                    'revoked_at' => $device->revoked_at?->toIso8601String(),
                ],
                device: $device,
            );

            return $device;
        });
    }

    /**
     * Register or update a device for an already-authenticated user.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function registerDeviceForAuthenticatedUser(
        User $user,
        array $attributes,
        ?int $personalAccessTokenId = null,
    ): Device {
        return DB::transaction(function () use ($user, $attributes, $personalAccessTokenId): Device {
            $device = Device::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'device_uuid' => (string) $attributes['device_uuid'],
                ],
                [
                    'personal_access_token_id' => $personalAccessTokenId,
                    'device_name' => (string) $attributes['device_name'],
                    'os_version' => $attributes['os_version'] ?? null,
                    'client_version' => $attributes['client_version'] ?? null,
                    'ets2_mod_path' => $attributes['ets2_mod_path'] ?? null,
                    'ats_mod_path' => $attributes['ats_mod_path'] ?? null,
                    'last_seen_at' => now(),
                    'revoked_at' => null,
                ]
            );

            $this->auditLogger->log(
                event: 'device.registered',
                auditable: $device,
                actor: $user,
                newValues: [
                    'device_uuid' => $device->device_uuid,
                    'device_name' => $device->device_name,
                    'os_version' => $device->os_version,
                    'client_version' => $device->client_version,
                ],
                device: $device,
            );

            return $device;
        });
    }

    /**
     * Update an existing device's metadata.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function updateDevice(Device $device, User $actor, array $attributes): Device
    {
        return DB::transaction(function () use ($device, $actor, $attributes): Device {
            $oldValues = [
                'device_name' => $device->device_name,
                'os_version' => $device->os_version,
                'client_version' => $device->client_version,
                'ets2_mod_path' => $device->ets2_mod_path,
                'ats_mod_path' => $device->ats_mod_path,
            ];

            $device->update([
                'device_name' => $attributes['device_name'] ?? $device->device_name,
                'os_version' => array_key_exists('os_version', $attributes) ? $attributes['os_version'] : $device->os_version,
                'client_version' => array_key_exists('client_version', $attributes) ? $attributes['client_version'] : $device->client_version,
                'ets2_mod_path' => array_key_exists('ets2_mod_path', $attributes) ? $attributes['ets2_mod_path'] : $device->ets2_mod_path,
                'ats_mod_path' => array_key_exists('ats_mod_path', $attributes) ? $attributes['ats_mod_path'] : $device->ats_mod_path,
                'last_seen_at' => now(),
            ]);

            $this->auditLogger->log(
                event: 'device.updated',
                auditable: $device,
                actor: $actor,
                oldValues: $oldValues,
                newValues: [
                    'device_name' => $device->device_name,
                    'os_version' => $device->os_version,
                    'client_version' => $device->client_version,
                    'ets2_mod_path' => $device->ets2_mod_path,
                    'ats_mod_path' => $device->ats_mod_path,
                ],
                device: $device,
            );

            return $device;
        });
    }

    /**
     * Update the device's last-seen timestamp and optional runtime version metadata.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function recordHeartbeat(Device $device, array $attributes = []): Device
    {
        $updates = [
            'last_seen_at' => now(),
        ];

        if (! empty($attributes['os_version'])) {
            $updates['os_version'] = (string) $attributes['os_version'];
        }

        if (! empty($attributes['client_version'])) {
            $updates['client_version'] = (string) $attributes['client_version'];
        }

        $device->update($updates);

        return $device;
    }

    /**
     * Permanently remove a device and revoke its associated Sanctum token.
     */
    public function removeDevice(Device $device, User $actor): void
    {
        DB::transaction(function () use ($device, $actor): void {
            if ($device->personal_access_token_id !== null) {
                $device->user->tokens()->whereKey($device->personal_access_token_id)->delete();
            }

            $this->auditLogger->log(
                event: 'device.removed',
                auditable: $device,
                actor: $actor,
                oldValues: [
                    'device_uuid' => $device->device_uuid,
                    'device_name' => $device->device_name,
                ],
            );

            $device->delete();
        });
    }
}
