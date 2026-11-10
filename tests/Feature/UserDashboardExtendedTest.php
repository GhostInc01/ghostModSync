<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\GameCode;
use App\Enums\GroupRole;
use App\Enums\GroupVisibility;
use App\Enums\MembershipStatus;
use App\Enums\ModSourceType;
use App\Enums\ModVersionStatus;
use App\Enums\SyncStatus;
use App\Models\AuditLog;
use App\Models\ConvoyProfile;
use App\Models\Device;
use App\Models\Game;
use App\Models\Group;
use App\Models\GroupInvitation;
use App\Models\GroupMembership;
use App\Models\Mod;
use App\Models\ModVersion;
use App\Models\SyncSession;
use App\Models\User;
use Database\Seeders\GameSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class UserDashboardExtendedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(GameSeeder::class);
    }

    private function join(User $user, Group $group, GroupRole $role = GroupRole::Member): void
    {
        GroupMembership::query()->create([
            'group_id' => $group->id,
            'user_id' => $user->id,
            'role' => $role,
            'status' => MembershipStatus::Active,
            'joined_at' => now(),
        ]);
    }

    private function game(GameCode $code = GameCode::Ets2): Game
    {
        return Game::query()->where('code', $code->value)->firstOrFail();
    }

    private function mod(Group $group, string $name, ModVersionStatus $status = ModVersionStatus::Ready, GameCode $code = GameCode::Ets2): Mod
    {
        $mod = Mod::query()->create([
            'group_id' => $group->id,
            'game_id' => $this->game($code)->id,
            'name' => $name,
            'slug' => Str::slug($name),
            'category' => 'general',
            'source_type' => ModSourceType::S3Upload,
            'is_active' => true,
        ]);
        ModVersion::query()->create([
            'mod_id' => $mod->id,
            'version_number' => '1.0.0',
            'archive_filename' => 'x.scs',
            'status' => $status,
            'published_at' => now(),
        ]);

        return $mod;
    }

    private function audit(string $event, ?User $user, ?Group $group): void
    {
        AuditLog::query()->create([
            'group_id' => $group?->id,
            'user_id' => $user?->id,
            'event' => $event,
            'auditable_type' => 'group',
            'auditable_id' => $group?->id ?? 1,
            'created_at' => now(),
        ]);
    }

    public function test_multiple_groups_with_different_roles_show_role_status_game_and_description(): void
    {
        $user = User::factory()->create();
        $a = Group::factory()->create(['name' => 'Alpha VTC', 'description' => 'Alpha heavy haul crew']);
        $b = Group::factory()->create(['name' => 'Bravo VTC', 'description' => null]);
        $this->join($user, $a, GroupRole::Owner);
        $this->join($user, $b, GroupRole::ConvoyDispatcher);
        $this->mod($a, 'Alpha Pack', code: GameCode::Ats);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSeeInOrder(['Alpha VTC', 'Bravo VTC'])
            ->assertSee('Group Owner')
            ->assertSee('Convoy Dispatcher')
            ->assertSee('Alpha heavy haul crew')
            ->assertSee('No description provided.')
            ->assertSee('American Truck Simulator')
            ->assertSee('Active')
            ->assertSee(route('groups.show', $a), false);
    }

    public function test_mods_available_counts_only_ready_mods_in_the_users_groups(): void
    {
        $user = User::factory()->create();
        $mine = Group::factory()->create();
        $other = Group::factory()->create();
        $this->join($user, $mine);
        $this->mod($mine, 'Ready Mod');
        $this->mod($mine, 'Pending Mod', ModVersionStatus::Pending);
        $this->mod($other, 'Foreign Mod');

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('kpis', fn (array $k) => $k['mods'] === 1)
            ->assertSee('Ready Mod')
            ->assertDontSee('Foreign Mod');
    }

    public function test_recent_activity_only_contains_real_events_visible_to_the_user(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();
        $mine = Group::factory()->create(['name' => 'My Crew']);
        $other = Group::factory()->create(['name' => 'Foreign Crew']);
        $this->join($user, $mine);

        $this->audit('membership.joined', $user, $mine);              // own -> shown
        $this->audit('mod_version.published_s3', $stranger, $mine);   // group event in my group -> shown
        $this->audit('membership.joined', $stranger, $mine);          // someone else's personal event -> hidden
        $this->audit('mod_version.published_s3', $stranger, $other);  // foreign group -> hidden
        $this->audit('device.authenticated', $user, null);            // not whitelisted -> hidden

        $response = $this->actingAs($user)->get(route('dashboard'))->assertOk();
        $response->assertViewHas('activity', fn ($a) => $a->count() === 2);
        $response->assertSee('You joined a group')->assertSee('A mod version was uploaded');
    }

    public function test_activity_shows_empty_state_without_events(): void
    {
        $this->actingAs(User::factory()->create())->get(route('dashboard'))
            ->assertOk()
            ->assertSee('No recorded activity yet.');
    }

    public function test_sync_overview_is_honest_without_devices_and_shows_failures_with_data(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('nothing is simulated here');

        $group = Group::factory()->create();
        $this->join($user, $group);
        $profile = ConvoyProfile::query()->create([
            'group_id' => $group->id,
            'game_id' => $this->game()->id,
            'name' => 'Night Convoy',
            'slug' => 'night-convoy',
            'target_game_version' => '1.53.*',
            'manifest_hash' => str_repeat('b', 64),
            'is_active' => true,
        ]);
        $device = Device::factory()->for($user)->create();
        SyncSession::query()->create([
            'convoy_profile_id' => $profile->id,
            'user_id' => $user->id,
            'device_id' => $device->id,
            'profile_revision' => 1,
            'status' => SyncStatus::Failed,
            'error_message' => 'Hash mismatch on mod',
        ]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('nothing is simulated here')
            ->assertSee('Sync Failed')
            ->assertSee('Hash mismatch on mod')
            ->assertViewHas('sync', fn (array $s) => $s['failed'] === 1);
    }

    public function test_request_parameters_cannot_change_whose_data_is_shown(): void
    {
        $user = User::factory()->create();
        $victim = User::factory()->create();
        $group = Group::factory()->create(['name' => 'Victim Only VTC']);
        $this->join($victim, $group);

        $this->actingAs($user)->get(route('dashboard', ['user_id' => $victim->id, 'user' => $victim->id, 'group_id' => $group->id]))
            ->assertOk()
            ->assertDontSee('Victim Only VTC')
            ->assertViewHas('kpis', fn (array $k) => $k['groups'] === 0);
    }

    public function test_attention_indicator_counts_actionable_items(): void
    {
        $user = User::factory()->create();
        $group = Group::factory()->create();
        GroupInvitation::query()->create([
            'group_id' => $group->id,
            'invited_by' => $group->owner_id,
            'email' => $user->email,
            'token' => Str::random(40),
            'role' => GroupRole::Member,
            'max_uses' => 1,
            'expires_at' => now()->addDay(),
        ]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('attentionCount', 1)
            ->assertSee('1 item needs your attention');
    }

    public function test_reserved_group_slugs_cannot_collide_with_discover_or_create_routes(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('groups.store'), [
            'name' => 'Custom VTC',
            'slug' => 'discover',
            'visibility' => GroupVisibility::Public->value,
            'sync_policy' => 'automatic',
        ])->assertSessionHasErrors('slug');

        $this->actingAs($user)->post(route('groups.store'), [
            'name' => 'Discover',
            'visibility' => GroupVisibility::Public->value,
            'sync_policy' => 'automatic',
        ])->assertRedirect();

        $created = Group::query()->where('name', 'Discover')->firstOrFail();
        $this->assertNotSame('discover', $created->slug);
        $this->assertStringStartsWith('discover-', $created->slug);
    }

    public function test_banned_user_does_not_see_invitations_and_email_matching_is_case_insensitive(): void
    {
        $user = User::factory()->create(['email' => 'Driver.Case@Example.com']);
        $bannedGroup = Group::factory()->create(['name' => 'Banned Private VTC']);
        $validGroup = Group::factory()->create(['name' => 'Case Insensitive VTC']);

        GroupMembership::query()->create([
            'group_id' => $bannedGroup->id,
            'user_id' => $user->id,
            'role' => GroupRole::Member,
            'status' => MembershipStatus::Banned,
        ]);

        GroupInvitation::query()->create([
            'group_id' => $bannedGroup->id,
            'invited_by' => $bannedGroup->owner_id,
            'email' => 'driver.case@example.com',
            'token' => Str::random(40),
            'role' => GroupRole::Member,
            'max_uses' => 1,
            'expires_at' => now()->addDay(),
        ]);

        GroupInvitation::query()->create([
            'group_id' => $validGroup->id,
            'invited_by' => $validGroup->owner_id,
            'email' => 'driver.case@example.com',
            'token' => Str::random(40),
            'role' => GroupRole::Member,
            'max_uses' => 1,
            'expires_at' => now()->addDay(),
        ]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Case Insensitive VTC')
            ->assertDontSee('Banned Private VTC')
            ->assertViewHas('attentionCount', 1);
    }

    public function test_inactive_mods_are_excluded_from_recent_updates_and_group_games(): void
    {
        $user = User::factory()->create();
        $group = Group::factory()->create(['name' => 'Clean Catalogue VTC']);
        $this->join($user, $group);

        $inactiveMod = $this->mod($group, 'Retired ATS Mod', ModVersionStatus::Ready, GameCode::Ats);
        $inactiveMod->update(['is_active' => false]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Retired ATS Mod')
            ->assertDontSee('American Truck Simulator');
    }

    public function test_self_authored_mod_and_profile_events_use_first_person_labels_and_avatar_renders(): void
    {
        $user = User::factory()->create(['avatar_path' => 'avatars/driver-1.png']);
        $group = Group::factory()->create(['name' => 'Self Actor VTC']);
        $this->join($user, $group);

        $this->audit('mod_version.published_s3', $user, $group);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('You uploaded a mod version')
            ->assertSee('avatars/driver-1.png', false);
    }

    public function test_sidebar_links_only_to_existing_features_and_marks_planned_ones(): void
    {
        $html = $this->actingAs(User::factory()->create())->get(route('dashboard'))->assertOk()->getContent();

        foreach ([route('dashboard'), route('groups.index'), route('groups.discover'), route('devices.index'), route('profile.edit'), route('logout')] as $url) {
            $this->assertStringContainsString($url, $html);
        }

        foreach (['My Mods', 'Convoy Profiles', 'Synchronisation', 'Notifications'] as $planned) {
            $this->assertMatchesRegularExpression('/aria-disabled="true">\s*<svg.*?<span>'.preg_quote($planned, '/').'<\/span>\s*<span[^>]*>Soon<\/span>/s', $html, "$planned should be a disabled 'Soon' item");
        }

        $this->assertStringContainsString('aria-current="page"', $html);
        $this->assertStringContainsString('aria-controls="dashboard-sidebar"', $html); // mobile toggle
        $this->assertStringNotContainsString('/admin', $html);
    }

    public function test_discover_requires_authentication_and_verification(): void
    {
        $this->get(route('groups.discover'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->unverified()->create())->get(route('groups.discover'))->assertRedirect(route('verification.notice'));
    }

    public function test_discover_lists_only_public_groups_and_shows_membership_state(): void
    {
        $user = User::factory()->create();
        $public = Group::factory()->public()->create(['name' => 'Open Roads VTC']);
        $joined = Group::factory()->public()->create(['name' => 'Joined Public VTC']);
        Group::factory()->create(['name' => 'Hidden Private VTC', 'visibility' => GroupVisibility::Private]);
        $this->join($user, $joined);

        $this->actingAs($user)->get(route('groups.discover'))
            ->assertOk()
            ->assertSee('Open Roads VTC')
            ->assertSee('Joined Public VTC')
            ->assertSee('Active')
            ->assertDontSee('Hidden Private VTC')
            ->assertSee(route('groups.show', $public), false);
    }

    public function test_discover_search_filters_and_treats_wildcards_literally(): void
    {
        $user = User::factory()->create();
        Group::factory()->public()->create(['name' => 'Alpine Haulers']);
        Group::factory()->public()->create(['name' => 'Desert Runners']);

        $this->actingAs($user)->get(route('groups.discover', ['q' => 'Alpine']))
            ->assertOk()->assertSee('Alpine Haulers')->assertDontSee('Desert Runners');

        $this->actingAs($user)->get(route('groups.discover', ['q' => '%']))
            ->assertOk()->assertSee('No public groups match your search.');
    }

    public function test_discover_rejects_invalid_search_input(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('groups.discover', ['q' => ['x']]))
            ->assertSessionHasErrors('q');
    }

    public function test_dashboard_query_count_does_not_grow_with_group_count(): void
    {
        $user = User::factory()->create();
        $make = function (int $n) use ($user): void {
            for ($i = 0; $i < $n; $i++) {
                $g = Group::factory()->create();
                $this->join($user, $g);
                $this->mod($g, 'Mod '.Str::random(6));
            }
        };

        $count = function () use ($user): int {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->actingAs($user)->get(route('dashboard'))->assertOk();

            return count(DB::getQueryLog());
        };

        $make(2);
        $small = $count();
        $make(6);
        $large = $count();

        $this->assertSame($small, $large, "Query count grew from $small to $large");
    }
}
