<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\GameCode;
use App\Models\AuditLog;
use App\Models\Device;
use App\Models\Game;
use App\Models\User;
use Database\Seeders\GameSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DeviceManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_game_seeder_populates_ets2_and_ats_with_versions(): void
    {
        $this->seed(GameSeeder::class);

        $this->assertDatabaseHas('games', [
            'code' => GameCode::Ets2->value,
            'steam_app_id' => 227300,
        ]);

        $this->assertDatabaseHas('games', [
            'code' => GameCode::Ats->value,
            'steam_app_id' => 270880,
        ]);

        $ets2 = Game::query()->where('code', GameCode::Ets2->value)->firstOrFail();
        $this->assertTrue($ets2->versions()->where('version_branch', '1.53')->where('is_current', true)->exists());
    }

    public function test_verified_user_can_authenticate_desktop_device_and_receive_sanctum_token(): void
    {
        $user = User::factory()->create([
            'password' => 'SecretTrucker123!',
        ]);

        $deviceUuid = (string) Str::uuid();

        $response = $this->postJson('/api/v1/auth/device-token', [
            'email' => $user->email,
            'password' => 'SecretTrucker123!',
            'device_uuid' => $deviceUuid,
            'device_name' => 'DESKTOP-SFG-01',
            'os_version' => 'Windows 11 Pro 23H2',
            'client_version' => '1.0.0',
            'ets2_mod_path' => 'C:\\Users\\Driver\\Documents\\Euro Truck Simulator 2\\mod',
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'token',
                'abilities',
                'user' => ['id', 'name', 'email'],
                'device' => ['id', 'device_uuid', 'device_name', 'is_revoked'],
            ]);

        $this->assertDatabaseHas('devices', [
            'user_id' => $user->id,
            'device_uuid' => $deviceUuid,
            'device_name' => 'DESKTOP-SFG-01',
            'revoked_at' => null,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'event' => 'device.authenticated',
        ]);

        $token = $response->json('token');

        $meResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/user');

        $meResponse->assertOk()
            ->assertJsonPath('user.email', $user->email)
            ->assertJsonPath('device.device_uuid', $deviceUuid);
    }

    public function test_unverified_user_cannot_issue_desktop_device_token(): void
    {
        $user = User::factory()->unverified()->create([
            'password' => 'SecretTrucker123!',
        ]);

        $response = $this->postJson('/api/v1/auth/device-token', [
            'email' => $user->email,
            'password' => 'SecretTrucker123!',
            'device_uuid' => (string) Str::uuid(),
            'device_name' => 'DESKTOP-UNVERIFIED',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('devices', 0);
    }

    public function test_user_can_revoke_own_device_via_web_and_cannot_revoke_other_users_device(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();

        $token = $owner->createToken('desktop:PC1');
        $device = Device::factory()->create([
            'user_id' => $owner->id,
            'personal_access_token_id' => $token->accessToken->id,
        ]);

        // Intruder cannot revoke owner's device
        $this->actingAs($intruder)
            ->delete(route('devices.destroy', $device))
            ->assertForbidden();

        $this->assertNull($device->fresh()?->revoked_at);

        // Owner can revoke own device
        $this->actingAs($owner)
            ->delete(route('devices.destroy', $device))
            ->assertRedirect(route('devices.index'));

        $this->assertNotNull($device->fresh()?->revoked_at);
        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $token->accessToken->id,
        ]);
        $this->assertTrue(AuditLog::query()->where('event', 'device.revoked')->exists());
    }

    public function test_multiple_devices_lifecycle_api_crud_heartbeat_cross_user_isolation_and_removal(): void
    {
        $user = User::factory()->create([
            'password' => 'DriverPass123!',
        ]);
        $otherUser = User::factory()->create();

        // 1. Register Device #1 via POST /api/v1/auth/device-token
        $uuid1 = (string) Str::uuid();
        $res1 = $this->postJson('/api/v1/auth/device-token', [
            'email' => $user->email,
            'password' => 'DriverPass123!',
            'device_uuid' => $uuid1,
            'device_name' => 'DESKTOP-SIMRIG-1',
            'os_version' => 'Windows 11 Pro 23H2',
            'client_version' => '1.0.0',
        ])->assertCreated();

        $token1 = (string) $res1->json('token');
        $device1Id = (int) $res1->json('device.id');

        // 2. Register Device #2 via authenticated POST /api/v1/devices
        $uuid2 = (string) Str::uuid();
        $res2 = $this->withHeader('Authorization', 'Bearer '.$token1)
            ->postJson('/api/v1/devices', [
                'device_uuid' => $uuid2,
                'device_name' => 'GAMING-LAPTOP-2',
                'os_version' => 'Windows 10 Pro 22H2',
                'client_version' => '1.0.1',
                'ets2_mod_path' => 'D:\\ETS2\\mod',
                'ats_mod_path' => 'D:\\ATS\\mod',
            ])->assertCreated()
            ->assertJsonPath('device.device_name', 'GAMING-LAPTOP-2')
            ->assertJsonPath('device.status', 'active');

        $device2Id = (int) $res2->json('device.id');

        // 3. List user's devices -> returns both devices
        $this->withHeader('Authorization', 'Bearer '.$token1)
            ->getJson('/api/v1/devices')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        // 4. View single device via GET /api/v1/devices/{device}
        $this->withHeader('Authorization', 'Bearer '.$token1)
            ->getJson("/api/v1/devices/{$device2Id}")
            ->assertOk()
            ->assertJsonPath('device.device_uuid', $uuid2)
            ->assertJsonPath('device.ets2_mod_path', 'D:\\ETS2\\mod');

        // 5. Update device metadata via PATCH /api/v1/devices/{device}
        $this->withHeader('Authorization', 'Bearer '.$token1)
            ->patchJson("/api/v1/devices/{$device2Id}", [
                'device_name' => 'GAMING-LAPTOP-2-UPDATED',
                'client_version' => '1.1.0',
            ])->assertOk()
            ->assertJsonPath('device.device_name', 'GAMING-LAPTOP-2-UPDATED')
            ->assertJsonPath('device.client_version', '1.1.0');

        // 6. Update last-seen heartbeat via POST /api/v1/devices/{device}/heartbeat
        $this->withHeader('Authorization', 'Bearer '.$token1)
            ->postJson("/api/v1/devices/{$device1Id}/heartbeat", [
                'client_version' => '1.1.2',
            ])->assertOk()
            ->assertJsonPath('device.client_version', '1.1.2');

        // 7. Cross-user isolation: otherUser cannot view, update, heartbeat, or remove user's devices
        $otherToken = $otherUser->createToken('desktop:OTHER')->plainTextToken;
        $this->app['auth']->forgetGuards();

        $this->withHeader('Authorization', 'Bearer '.$otherToken)
            ->getJson("/api/v1/devices/{$device1Id}")
            ->assertForbidden();

        $this->withHeader('Authorization', 'Bearer '.$otherToken)
            ->patchJson("/api/v1/devices/{$device1Id}", ['device_name' => 'HACKED'])
            ->assertForbidden();

        $this->withHeader('Authorization', 'Bearer '.$otherToken)
            ->postJson("/api/v1/devices/{$device1Id}/heartbeat")
            ->assertForbidden();

        $this->withHeader('Authorization', 'Bearer '.$otherToken)
            ->deleteJson("/api/v1/devices/{$device1Id}?remove=1")
            ->assertForbidden();

        // 8. Permanently remove Device #2 via DELETE /api/v1/devices/{device}?remove=1
        $this->app['auth']->forgetGuards();
        $this->withHeader('Authorization', 'Bearer '.$token1)
            ->deleteJson("/api/v1/devices/{$device2Id}?remove=1")
            ->assertNoContent();

        $this->assertDatabaseMissing('devices', ['id' => $device2Id]);
        $this->assertTrue(AuditLog::query()->where('event', 'device.registered')->exists());
        $this->assertTrue(AuditLog::query()->where('event', 'device.updated')->exists());
        $this->assertTrue(AuditLog::query()->where('event', 'device.removed')->exists());
    }
}
