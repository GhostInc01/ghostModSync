<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\GameCode;
use App\Enums\GroupRole;
use App\Enums\GroupVisibility;
use App\Enums\JoinRequestStatus;
use App\Enums\MembershipStatus;
use App\Enums\SyncStatus;
use App\Models\ConvoyProfile;
use App\Models\Device;
use App\Models\Game;
use App\Models\Group;
use App\Models\GroupInvitation;
use App\Models\GroupJoinRequest;
use App\Models\GroupMembership;
use App\Models\SyncApproval;
use App\Models\SyncSession;
use App\Models\User;
use App\Services\Dashboard\DashboardOverviewService;
use Database\Seeders\GameSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class UserDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(GameSeeder::class);
    }

    private function join(User $user, Group $group, GroupRole $role = GroupRole::Member, MembershipStatus $status = MembershipStatus::Active): void
    {
        GroupMembership::query()->create([
            'group_id' => $group->id,
            'user_id' => $user->id,
            'role' => $role,
            'status' => $status,
            'joined_at' => now(),
        ]);
    }

    private function profile(Group $group, string $name = 'Sunday Convoy'): ConvoyProfile
    {
        return ConvoyProfile::query()->create([
            'group_id' => $group->id,
            'game_id' => Game::query()->where('code', GameCode::Ets2->value)->firstOrFail()->id,
            'name' => $name,
            'slug' => Str::slug($name),
            'target_game_version' => '1.53.*',
            'manifest_hash' => str_repeat('a', 64),
            'is_active' => true,
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_unverified_user_is_redirected_to_verification(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('verification.notice'));
    }

    public function test_new_user_sees_empty_states_with_real_zero_counts(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Welcome back, '.$user->name)
            ->assertSee('You haven&#039;t joined a group yet.', false)
            ->assertSee('No sync sessions yet')
            ->assertSee('Getting started')
            ->assertViewHas('kpis', fn (array $k) => $k['groups'] === 0 && $k['devices'] === 0 && $k['profiles'] === 0 && $k['mods'] === 0 && $k['last_sync'] === null);
    }

    public function test_dashboard_shows_only_the_users_own_active_groups_and_data(): void
    {
        $user = User::factory()->create();
        $mine = Group::factory()->create(['name' => 'Alpha Haulage']);
        $privateOther = Group::factory()->create(['name' => 'Secret Private VTC', 'visibility' => GroupVisibility::Private]);
        $suspended = Group::factory()->create(['name' => 'Suspended Group']);
        $this->join($user, $mine, GroupRole::ModManager);
        $this->join($user, $suspended, GroupRole::Member, MembershipStatus::Suspended);

        $profile = $this->profile($mine);
        $this->profile($privateOther, 'Hidden Convoy');
        $device = Device::factory()->for($user)->create(['device_name' => 'DESKTOP-MINE']);
        Device::factory()->create(['device_name' => 'DESKTOP-STRANGER']);

        SyncSession::query()->create([
            'convoy_profile_id' => $profile->id,
            'user_id' => $user->id,
            'device_id' => $device->id,
            'profile_revision' => 1,
            'status' => SyncStatus::OutOfSync,
        ]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Alpha Haulage')
            ->assertSee('Mod Manager')
            ->assertSee('Sunday Convoy')
            ->assertSee('Out of Sync')
            ->assertSee('DESKTOP-MINE')
            ->assertDontSee('Secret Private VTC')
            ->assertDontSee('Hidden Convoy')
            ->assertDontSee('Suspended Group')
            ->assertDontSee('DESKTOP-STRANGER')
            ->assertViewHas('kpis', fn (array $k) => $k['groups'] === 1 && $k['profiles'] === 1 && $k['devices'] === 1 && $k['out_of_sync'] === 1);
    }

    public function test_sync_approvals_are_only_visible_to_roles_that_can_approve(): void
    {
        $group = Group::factory()->create();
        $requester = User::factory()->create(['name' => 'Requesting Rita']);
        $dispatcher = User::factory()->create();
        $member = User::factory()->create();
        $this->join($requester, $group);
        $this->join($dispatcher, $group, GroupRole::ConvoyDispatcher);
        $this->join($member, $group);

        $profile = $this->profile($group);
        SyncApproval::query()->create([
            'convoy_profile_id' => $profile->id,
            'user_id' => $requester->id,
            'approved_revision' => 1,
            'status' => 'pending',
        ]);

        $this->actingAs($dispatcher)->get(route('dashboard'))->assertOk()->assertSee('Requesting Rita');
        $this->actingAs($member)->get(route('dashboard'))->assertOk()
            ->assertDontSee('Requesting Rita')
            ->assertViewHas('approvals', fn ($a) => $a->isEmpty());
    }

    public function test_platform_admin_flag_grants_no_extra_dashboard_data(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_platform_admin' => true])->save();
        Group::factory()->create(['name' => 'Someone Elses VTC']);

        $this->actingAs($admin)->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Someone Elses VTC')
            ->assertViewHas('kpis', fn (array $k) => $k['groups'] === 0);
    }

    public function test_pending_invitations_and_join_requests_are_scoped_to_the_user(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $inviting = Group::factory()->create(['name' => 'Inviting VTC']);
        $requested = Group::factory()->create(['name' => 'Requested VTC']);
        $unrelated = Group::factory()->create(['name' => 'Unrelated VTC']);

        GroupInvitation::query()->create([
            'group_id' => $inviting->id,
            'invited_by' => $inviting->owner_id,
            'email' => $user->email,
            'token' => Str::random(40),
            'role' => GroupRole::Member,
            'max_uses' => 1,
            'expires_at' => now()->addDay(),
        ]);
        GroupInvitation::query()->create([
            'group_id' => $unrelated->id,
            'invited_by' => $unrelated->owner_id,
            'email' => $other->email,
            'token' => Str::random(40),
            'role' => GroupRole::Member,
            'max_uses' => 1,
            'expires_at' => now()->addDay(),
        ]);
        GroupJoinRequest::query()->create([
            'group_id' => $requested->id,
            'user_id' => $user->id,
            'status' => JoinRequestStatus::Pending,
        ]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Inviting VTC')
            ->assertSee('Requested VTC')
            ->assertDontSee('Unrelated VTC');
    }

    public function test_dashboard_degrades_to_error_state_when_loading_fails(): void
    {
        $this->mock(DashboardOverviewService::class)
            ->shouldReceive('forUser')->andThrow(new RuntimeException('boom'));

        $this->actingAs(User::factory()->create())->get(route('dashboard'))
            ->assertOk()
            ->assertSee("We couldn't load your dashboard", false);
    }

    public function test_dashboard_uses_the_dark_gms_layout_and_links_existing_routes_only(): void
    {
        $this->actingAs(User::factory()->create())->get(route('dashboard'))
            ->assertOk()
            ->assertSee('bg-gms-bg', false)
            ->assertSee(route('groups.index'), false)
            ->assertSee(route('devices.index'), false)
            ->assertSee(route('profile.edit'), false);
    }
}
