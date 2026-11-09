<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Device;
use App\Models\User;
use Database\Seeders\GameSeeder;
use Database\Seeders\SfgPilotSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthenticationAndApiFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            GameSeeder::class,
            SfgPilotSeeder::class,
        ]);
    }

    public function test_browser_login_updates_last_login_at_and_protects_against_mass_assignment(): void
    {
        $user = User::factory()->create([
            'password' => 'StrongPassword123!',
            'last_login_at' => null,
            'is_platform_admin' => false,
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'StrongPassword123!',
        ])->assertRedirect(route('dashboard', absolute: false));

        $user->refresh();
        $this->assertNotNull($user->last_login_at);

        // Attempt to escalate is_platform_admin via profile update -> ignored by FormRequest & $fillable
        $this->actingAs($user)->patch('/profile', [
            'name' => 'Updated Driver',
            'username' => 'sfg_driver_99',
            'steam_id' => '76561198012345678',
            'email' => $user->email,
            'is_platform_admin' => true,
        ])->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertSame('sfg_driver_99', $user->username);
        $this->assertSame('76561198012345678', $user->steam_id);
        $this->assertFalse($user->is_platform_admin);
    }

    public function test_unauthenticated_api_requests_return_consistent_401_json_error(): void
    {
        $this->getJson('/api/v1/user')
            ->assertUnauthorized()
            ->assertExactJson([
                'status' => 'error',
                'error_code' => 'unauthenticated',
                'message' => 'Unauthenticated.',
            ]);

        $this->getJson('/api/v1/groups')
            ->assertUnauthorized()
            ->assertJsonPath('error_code', 'unauthenticated');
    }

    public function test_api_token_is_hashed_with_sha256_in_database_and_user_resource_hides_sensitive_fields(): void
    {
        $user = User::factory()->create([
            'password' => 'DesktopSecret123!',
            'is_platform_admin' => true,
        ]);

        $deviceUuid = (string) Str::uuid();

        $response = $this->postJson('/api/v1/auth/device-token', [
            'email' => $user->email,
            'password' => 'DesktopSecret123!',
            'device_uuid' => $deviceUuid,
            'device_name' => 'WIN11-SIM-RIG',
        ]);

        $response->assertCreated()
            ->assertJsonPath('status', 'ok')
            ->assertJsonMissingPath('user.password')
            ->assertJsonMissingPath('user.remember_token')
            ->assertJsonMissingPath('user.is_platform_admin');

        $plainTextToken = (string) $response->json('token');
        [$tokenId, $rawSecret] = explode('|', $plainTextToken, 2);

        /** @var PersonalAccessToken $storedToken */
        $storedToken = PersonalAccessToken::query()->findOrFail((int) $tokenId);

        // Plaintext secret is never stored; only its SHA-256 hex digest is stored
        $this->assertNotSame($rawSecret, $storedToken->token);
        $this->assertSame(hash('sha256', $rawSecret), $storedToken->token);
    }

    public function test_invalid_and_revoked_device_tokens_are_rejected_with_401(): void
    {
        $user = User::factory()->create([
            'password' => 'DesktopSecret123!',
        ]);

        $issueRes = $this->postJson('/api/v1/auth/device-token', [
            'email' => $user->email,
            'password' => 'DesktopSecret123!',
            'device_uuid' => (string) Str::uuid(),
            'device_name' => 'RIG-TO-REVOKE',
        ])->assertCreated();

        $token = (string) $issueRes->json('token');
        $deviceId = (int) $issueRes->json('device.id');

        // 1. Invalid token secret returns 401
        $this->withHeader('Authorization', 'Bearer 999|invalid_secret_token_value')
            ->getJson('/api/v1/user')
            ->assertUnauthorized()
            ->assertJsonPath('error_code', 'unauthenticated');

        // 2. If the Device record is marked revoked_at, EnsureActiveDeviceToken middleware rejects the token with 401
        Device::query()->whereKey($deviceId)->update(['revoked_at' => now()]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/user')
            ->assertUnauthorized()
            ->assertJsonPath('error_code', 'device_revoked');
    }

    public function test_token_abilities_are_enforced_on_api_v1_endpoints(): void
    {
        $user = User::factory()->create();

        // Issue a restricted token that ONLY has 'groups:read' (missing 'profiles:read')
        Sanctum::actingAs($user, ['groups:read']);

        // Allowed: has 'groups:read'
        $this->getJson('/api/v1/groups')
            ->assertOk();

        // Forbidden: lacks 'profiles:read'
        $this->getJson('/api/v1/groups/sfg/profiles')
            ->assertForbidden()
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('error_code', 'insufficient_token_abilities');
    }

    public function test_desktop_token_can_be_revoked_via_logout_or_device_management_api(): void
    {
        $user = User::factory()->create([
            'password' => 'DesktopSecret123!',
        ]);

        $res1 = $this->postJson('/api/v1/auth/device-token', [
            'email' => $user->email,
            'password' => 'DesktopSecret123!',
            'device_uuid' => (string) Str::uuid(),
            'device_name' => 'PRIMARY-PC',
        ])->assertCreated();

        $res2 = $this->postJson('/api/v1/auth/device-token', [
            'email' => $user->email,
            'password' => 'DesktopSecret123!',
            'device_uuid' => (string) Str::uuid(),
            'device_name' => 'LAPTOP-PC',
        ])->assertCreated();

        $token1 = (string) $res1->json('token');
        $device2Id = (int) $res2->json('device.id');

        // List user's devices via GET /api/v1/devices
        $this->withHeader('Authorization', 'Bearer '.$token1)
            ->getJson('/api/v1/devices')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        // Revoke LAPTOP-PC from PRIMARY-PC via DELETE /api/v1/devices/{device}
        $this->withHeader('Authorization', 'Bearer '.$token1)
            ->deleteJson("/api/v1/devices/{$device2Id}")
            ->assertOk()
            ->assertJsonPath('device.is_revoked', true);

        // Logout PRIMARY-PC via DELETE /api/v1/auth/device-token
        $this->withHeader('Authorization', 'Bearer '.$token1)
            ->deleteJson('/api/v1/auth/device-token')
            ->assertNoContent();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_api_v1_returns_structured_validation_and_not_found_errors(): void
    {
        // 422 Validation Error structure
        $this->postJson('/api/v1/auth/device-token', [
            'email' => 'not-an-email',
        ])->assertStatus(422)
            ->assertJsonStructure([
                'status',
                'error_code',
                'message',
                'errors' => ['email', 'password', 'device_uuid', 'device_name'],
            ])
            ->assertJsonPath('error_code', 'validation_failed');

        // 404 Not Found Error structure
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['groups:read']);

        $this->getJson('/api/v1/groups/non-existent-vtc-slug')
            ->assertNotFound()
            ->assertExactJson([
                'status' => 'error',
                'error_code' => 'not_found',
                'message' => 'The requested resource was not found.',
            ]);
    }
}
