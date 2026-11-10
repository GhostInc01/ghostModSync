<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\GameCode;
use App\Enums\GroupRole;
use App\Enums\MembershipStatus;
use App\Enums\ModApprovalStatus;
use App\Enums\ModerationReportStatus;
use App\Enums\ModSourceType;
use App\Enums\ModVersionStatus;
use App\Enums\PlatformRole;
use App\Enums\SyncStatus;
use App\Models\AuditLog;
use App\Models\ConvoyProfile;
use App\Models\Device;
use App\Models\Game;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\GroupModApproval;
use App\Models\Mod;
use App\Models\ModerationReport;
use App\Models\ModVersion;
use App\Models\SyncSession;
use App\Models\User;
use Database\Seeders\GameSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(GameSeeder::class);
    }

    public function test_guest_is_redirected_to_login_from_admin_routes(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
        $this->get(route('admin.users.index'))->assertRedirect(route('login'));
        $this->get(route('admin.settings.index'))->assertRedirect(route('login'));
    }

    public function test_ordinary_users_and_group_admins_cannot_access_platform_admin_dashboard(): void
    {
        $ordinary = User::factory()->create();
        $groupOwner = User::factory()->create();
        $groupAdmin = User::factory()->create();
        $groupModerator = User::factory()->create();

        $group = Group::factory()->create(['owner_id' => $groupOwner->id]);

        foreach ([
            [$groupOwner, GroupRole::Owner],
            [$groupAdmin, GroupRole::Admin],
            [$groupModerator, GroupRole::Moderator],
            [$ordinary, GroupRole::Member],
        ] as [$member, $role]) {
            GroupMembership::query()->create([
                'group_id' => $group->id,
                'user_id' => $member->id,
                'role' => $role,
                'status' => MembershipStatus::Active,
                'joined_at' => now(),
            ]);

            $this->actingAs($member)->get(route('admin.dashboard'))->assertForbidden();
            $this->actingAs($member)->get(route('admin.users.index'))->assertForbidden();
            $this->actingAs($member)->get(route('admin.groups.index'))->assertForbidden();
            $this->actingAs($member)->get(route('admin.mods.index'))->assertForbidden();
            $this->actingAs($member)->get(route('admin.settings.index'))->assertForbidden();
        }
    }

    public function test_platform_admin_and_super_admin_can_view_all_ten_sections_with_real_metrics(): void
    {
        $superAdmin = User::factory()->superAdmin()->create(['name' => 'Super Ghost']);
        $platformAdmin = User::factory()->platformAdmin()->create(['name' => 'Ops Admin']);

        $group = Group::factory()->create(['name' => 'Special Forces Group']);
        $ets2 = Game::query()->where('code', GameCode::Ets2->value)->firstOrFail();

        $mod = Mod::query()->create([
            'group_id' => $group->id,
            'game_id' => $ets2->id,
            'created_by' => $superAdmin->id,
            'name' => 'SFG Heavy Cargo Pack',
            'slug' => 'sfg-heavy-cargo-pack',
            'category' => 'trailer',
            'source_type' => ModSourceType::S3Upload,
            'is_active' => true,
        ]);

        $modVersion = ModVersion::query()->create([
            'mod_id' => $mod->id,
            'uploaded_by' => $superAdmin->id,
            'version_number' => '1.2.0',
            'min_game_version' => '1.53',
            'archive_filename' => 'sfg_heavy_cargo_120.scs',
            'file_size_bytes' => 104857600,
            'sha256_hash' => str_repeat('b', 64),
            'status' => ModVersionStatus::Ready,
            'published_at' => now(),
        ]);

        $profile = ConvoyProfile::query()->create([
            'group_id' => $group->id,
            'game_id' => $ets2->id,
            'created_by' => $superAdmin->id,
            'name' => 'SFG Weekly Convoy',
            'slug' => 'sfg-weekly-convoy',
            'target_game_version' => '1.53.*',
            'revision' => 2,
            'manifest_hash' => str_repeat('c', 64),
            'is_active' => true,
            'published_at' => now(),
        ]);

        $device = Device::factory()->for($superAdmin)->create(['device_name' => 'GHOST-RIG-01']);

        SyncSession::query()->create([
            'convoy_profile_id' => $profile->id,
            'user_id' => $superAdmin->id,
            'device_id' => $device->id,
            'profile_revision' => 2,
            'status' => SyncStatus::InSync,
            'bytes_downloaded' => 104857600,
            'duration_ms' => 4200,
        ]);

        foreach ([$superAdmin, $platformAdmin] as $actor) {
            $this->actingAs($actor)->get(route('admin.dashboard'))
                ->assertOk()
                ->assertSee('Platform Overview')
                ->assertViewHas('groups', fn (array $g) => $g['total'] === 1)
                ->assertViewHas('mods', fn (array $m) => $m['total'] === 1 && $m['ready_versions'] === 1)
                ->assertViewHas('convoys', fn (array $c) => $c['published'] === 1);

            $this->actingAs($actor)->get(route('admin.users.index'))
                ->assertOk()
                ->assertSee('Super Ghost')
                ->assertSee('Ops Admin');

            $this->actingAs($actor)->get(route('admin.users.show', $superAdmin))
                ->assertOk()
                ->assertSee('GHOST-RIG-01');

            $this->actingAs($actor)->get(route('admin.groups.index'))
                ->assertOk()
                ->assertSee('Special Forces Group');

            $this->actingAs($actor)->get(route('admin.groups.show', $group->id))
                ->assertOk()
                ->assertSee('Special Forces Group');

            $this->actingAs($actor)->get(route('admin.mods.index'))
                ->assertOk()
                ->assertSee('SFG Heavy Cargo Pack');

            $this->actingAs($actor)->get(route('admin.mods.show', $mod->id))
                ->assertOk()
                ->assertSee('sfg_heavy_cargo_120.scs');

            $this->actingAs($actor)->get(route('admin.convoys.index'))
                ->assertOk()
                ->assertSee('SFG Weekly Convoy');

            $this->actingAs($actor)->get(route('admin.convoys.show', $profile->id))
                ->assertOk()
                ->assertSee('SFG Weekly Convoy');

            $this->actingAs($actor)->get(route('admin.sync.index'))
                ->assertOk()
                ->assertSee('GHOST-RIG-01');

            $this->actingAs($actor)->get(route('admin.storage.index'))
                ->assertOk()
                ->assertSee('Special Forces Group')
                ->assertSee('sfg_heavy_cargo_120.scs');

            $this->actingAs($actor)->get(route('admin.reports.index'))
                ->assertOk()
                ->assertSee('Export Users CSV');

            $this->actingAs($actor)->get(route('admin.reports.export', 'users'))
                ->assertOk()
                ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

            $this->actingAs($actor)->get(route('admin.audit-logs.index'))
                ->assertOk();

            $this->actingAs($actor)->get(route('admin.settings.index'))
                ->assertOk()
                ->assertSee('Euro Truck Simulator 2');
        }
    }

    public function test_platform_admin_can_suspend_and_reactivate_user_and_suspended_user_cannot_login_or_use_api(): void
    {
        $admin = User::factory()->platformAdmin()->create();
        $target = User::factory()->create(['email' => 'driver@example.com']);
        $device = Device::factory()->for($target)->create();
        $plainToken = $target->createToken('desktop-client')->plainTextToken;

        // Self-suspension is blocked
        $this->actingAs($admin)->patch(route('admin.users.status.update', $admin), [
            'action' => 'suspend',
            'reason' => 'Testing self lock',
        ])->assertSessionHasErrors('action');
        $this->assertFalse($admin->fresh()->isSuspended());

        // Suspend target user
        $this->actingAs($admin)->patch(route('admin.users.status.update', $target), [
            'action' => 'suspend',
            'reason' => 'Uploading corrupted mod archives',
        ])->assertRedirect(route('admin.users.show', $target));

        $target->refresh();
        $this->assertTrue($target->isSuspended());
        $this->assertSame('Uploading corrupted mod archives', $target->suspended_reason);
        $this->assertSame(0, $target->tokens()->count());
        $this->assertTrue(AuditLog::query()->where('event', 'admin.user.suspended')->where('auditable_id', $target->id)->exists());

        // Suspended user cannot log in via web form
        $this->app['auth']->forgetGuards();
        $this->post('/login', [
            'email' => 'driver@example.com',
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        // Reactivate target user
        $this->actingAs($admin)->patch(route('admin.users.status.update', $target), [
            'action' => 'reactivate',
        ])->assertRedirect(route('admin.users.show', $target));

        $this->assertFalse($target->fresh()->isSuspended());
        $this->assertTrue(AuditLog::query()->where('event', 'admin.user.reactivated')->where('auditable_id', $target->id)->exists());

        // Revoke target user's desktop device
        $this->actingAs($admin)->delete(route('admin.users.devices.revoke', [$target, $device]))
            ->assertRedirect(route('admin.users.show', $target));

        $this->assertNotNull($device->fresh()->revoked_at);
        $this->assertTrue(AuditLog::query()->where('event', 'device.revoked')->where('auditable_id', $device->id)->exists());
    }

    public function test_only_super_admin_can_assign_platform_roles_or_modify_system_settings(): void
    {
        $platformAdmin = User::factory()->platformAdmin()->create();
        $superAdmin = User::factory()->superAdmin()->create();
        $target = User::factory()->create();
        $ets2 = Game::query()->where('code', GameCode::Ets2->value)->firstOrFail();

        // PlatformAdmin is forbidden from promoting users to platform roles
        $this->actingAs($platformAdmin)->patch(route('admin.users.status.update', $target), [
            'action' => 'update_role',
            'platform_role' => PlatformRole::PlatformAdmin->value,
        ])->assertForbidden();

        // PlatformAdmin is forbidden from adding game versions or toggling game status
        $this->actingAs($platformAdmin)->post(route('admin.settings.game-versions.store'), [
            'game_id' => $ets2->id,
            'version_branch' => '1.54.*',
            'is_current' => true,
        ])->assertForbidden();

        $this->actingAs($platformAdmin)->patch(route('admin.settings.games.update', $ets2), [
            'is_active' => false,
        ])->assertForbidden();

        // SuperAdmin can promote user and manage game versions
        $this->actingAs($superAdmin)->patch(route('admin.users.status.update', $target), [
            'action' => 'update_role',
            'platform_role' => PlatformRole::PlatformAdmin->value,
        ])->assertRedirect(route('admin.users.show', $target));

        $this->assertSame(PlatformRole::PlatformAdmin, $target->fresh()->platform_role);
        $this->assertTrue(AuditLog::query()->where('event', 'admin.user.role_updated')->where('auditable_id', $target->id)->exists());

        $this->actingAs($superAdmin)->post(route('admin.settings.game-versions.store'), [
            'game_id' => $ets2->id,
            'version_branch' => '1.54.*',
            'is_current' => true,
        ])->assertRedirect(route('admin.settings.index'));

        $this->assertDatabaseHas('game_versions', [
            'game_id' => $ets2->id,
            'version_branch' => '1.54.*',
            'is_current' => 1,
        ]);
    }

    public function test_platform_admin_can_archive_restore_and_update_group_quota_and_moderate_mods_and_reports(): void
    {
        $admin = User::factory()->platformAdmin()->create();
        $group = Group::factory()->create(['name' => 'Nordic Logistics']);
        $ets2 = Game::query()->where('code', GameCode::Ets2->value)->firstOrFail();

        // Archive group
        $this->actingAs($admin)->patch(route('admin.groups.status.update', $group->id), [
            'action' => 'archive',
            'reason' => 'Inactive VTC',
        ])->assertRedirect(route('admin.groups.show', $group->id));
        $this->assertTrue($group->fresh()->trashed());

        // Restore group
        $this->actingAs($admin)->patch(route('admin.groups.status.update', $group->id), [
            'action' => 'restore',
        ])->assertRedirect(route('admin.groups.show', $group->id));
        $this->assertFalse($group->fresh()->trashed());

        // Update quota to 50 GB
        $this->actingAs($admin)->patch(route('admin.groups.status.update', $group->id), [
            'action' => 'update_quota',
            'storage_quota_gb' => 50,
        ])->assertRedirect(route('admin.groups.show', $group->id));
        $this->assertSame(50 * 1073741824, $group->fresh()->storage_quota_bytes);

        // Mod version & approval moderation
        $mod = Mod::query()->create([
            'group_id' => $group->id,
            'game_id' => $ets2->id,
            'created_by' => $admin->id,
            'name' => 'Nordic Winter Physics',
            'slug' => 'nordic-winter-physics-'.Str::lower(Str::random(4)),
            'category' => 'physics',
            'source_type' => ModSourceType::S3Upload,
            'is_active' => true,
        ]);

        $version = ModVersion::query()->create([
            'mod_id' => $mod->id,
            'uploaded_by' => $admin->id,
            'version_number' => '1.0.0',
            'archive_filename' => 'nordic_winter.scs',
            'file_size_bytes' => 2048,
            'sha256_hash' => str_repeat('d', 64),
            'status' => ModVersionStatus::Ready,
        ]);

        $approval = GroupModApproval::query()->create([
            'group_id' => $group->id,
            'mod_version_id' => $version->id,
            'status' => ModApprovalStatus::Pending,
        ]);

        $report = ModerationReport::query()->create([
            'reporter_id' => $admin->id,
            'reportable_type' => Mod::class,
            'reportable_id' => $mod->id,
            'reason' => 'Corrupted manifest inside archive',
            'status' => ModerationReportStatus::Pending,
        ]);

        $this->actingAs($admin)->patch(route('admin.mod-versions.status.update', $version), [
            'status' => 'revoked',
            'reason' => 'Confirmed broken archive',
        ])->assertRedirect(route('admin.mods.show', $mod->id));
        $this->assertSame(ModVersionStatus::Revoked, $version->fresh()->status);

        $this->actingAs($admin)->patch(route('admin.mod-approvals.review', $approval), [
            'status' => 'rejected',
            'review_notes' => 'Rejected due to revoked archive',
        ])->assertRedirect();
        $this->assertSame(ModApprovalStatus::Rejected, $approval->fresh()->status);

        $this->actingAs($admin)->patch(route('admin.moderation-reports.review', $report), [
            'status' => 'resolved',
            'review_notes' => 'Revoked offending mod version v1.0.0',
        ])->assertRedirect();
        $this->assertSame(ModerationReportStatus::Resolved, $report->fresh()->status);
    }

    public function test_search_filtering_pagination_date_ranges_and_responsive_admin_layout(): void
    {
        $admin = User::factory()->superAdmin()->create(['name' => 'Chief Admin']);
        User::factory()->create(['name' => 'Active Alice']);
        $suspendedUser = User::factory()->create([
            'name' => 'Suspended Sam',
            'suspended_at' => now(),
            'suspended_reason' => 'Policy violation',
        ]);
        User::factory()->count(21)->create();

        // User search, filter, and pagination
        $this->actingAs($admin)->get(route('admin.users.index', ['q' => 'Suspended Sam']))
            ->assertOk()
            ->assertSee('Registered users (1)')
            ->assertSee('Suspended Sam')
            ->assertDontSee('Active Alice');

        $this->actingAs($admin)->get(route('admin.users.index', ['filter' => 'suspended']))
            ->assertOk()
            ->assertSee('Registered users (1)')
            ->assertSee('Suspended Sam')
            ->assertDontSee('Active Alice');

        $this->actingAs($admin)->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Page 1 of 2');

        // Audit log search, category, and date range filtering
        AuditLog::query()->create([
            'user_id' => $admin->id,
            'event' => 'admin.custom.check',
            'auditable_type' => User::class,
            'auditable_id' => $suspendedUser->id,
            'created_at' => now(),
        ]);

        $today = now()->toDateString();
        $this->actingAs($admin)->get(route('admin.audit-logs.index', [
            'q' => 'admin.custom.check',
            'category' => 'admin',
            'from' => $today,
            'to' => $today,
        ]))
            ->assertOk()
            ->assertSee('admin.custom.check');

        // Responsive admin layout + secret non-exposure on System Settings
        $this->actingAs($admin)->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('id="admin-sidebar"', false)
            ->assertSee('-translate-x-full', false)
            ->assertSee('lg:translate-x-0', false)
            ->assertSee('lg:pl-64', false)
            ->assertDontSee((string) config('app.key'));
    }

    public function test_qa_security_hardening_super_admin_protection_idor_and_csv_formula_sanitisation(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $platformAdmin = User::factory()->platformAdmin()->create();
        $formulaUser = User::factory()->create(['name' => '=cmd|\'/C calc\'!A0']);
        $superDevice = Device::factory()->for($superAdmin)->create();
        $otherDevice = Device::factory()->for($formulaUser)->create();

        // 1. PlatformAdmin cannot suspend or revoke devices of a SuperAdmin (403 Forbidden)
        $this->actingAs($platformAdmin)->patch(route('admin.users.status.update', $superAdmin), [
            'action' => 'suspend',
            'reason' => 'Attempting horizontal/vertical takeover',
        ])->assertForbidden();

        $this->actingAs($platformAdmin)->delete(route('admin.users.devices.revoke', [$superAdmin, $superDevice]))
            ->assertForbidden();

        // 2. Cross-user device revocation IDOR returns 404 Not Found
        $this->actingAs($superAdmin)->delete(route('admin.users.devices.revoke', [$platformAdmin, $otherDevice]))
            ->assertNotFound();

        // 3. CSV export neutralises formula injection payloads with leading single quote
        $csvResponse = $this->actingAs($platformAdmin)->get(route('admin.reports.export', 'users'));
        $csvResponse->assertOk();
        $csvContent = $csvResponse->streamedContent();
        $this->assertStringContainsString("'=cmd|'/C calc'!A0", $csvContent);
    }

    public function test_security_audit_comprehensive_pen_tests_and_integrity_invariants(): void
    {
        $superAdmin = User::factory()->superAdmin()->create(['email' => 'super@example.com']);
        $platformAdmin = User::factory()->platformAdmin()->create();
        $ordinaryUser = User::factory()->create();
        $group = Group::factory()->create();
        $ets2 = Game::query()->where('code', GameCode::Ets2->value)->firstOrFail();

        $mod = Mod::query()->create([
            'group_id' => $group->id,
            'game_id' => $ets2->id,
            'created_by' => $superAdmin->id,
            'name' => 'Pen Test Mod',
            'slug' => 'pen-test-mod',
            'category' => 'truck',
            'source_type' => ModSourceType::S3Upload,
            'is_active' => true,
        ]);

        $modVersion = ModVersion::query()->create([
            'mod_id' => $mod->id,
            'uploaded_by' => $superAdmin->id,
            'version_number' => '1.0.0',
            'archive_filename' => 'pen_test.scs',
            'file_size_bytes' => 1024,
            'sha256_hash' => str_repeat('e', 64),
            'status' => ModVersionStatus::Ready,
        ]);

        // 1. Invalid CSV export type produces 404 Not Found
        $this->actingAs($platformAdmin)->get(route('admin.reports.export', 'credentials'))
            ->assertNotFound();

        // 2. Out-of-bounds group storage quota validation (min: 1, max: 10000)
        $this->actingAs($platformAdmin)->patch(route('admin.groups.status.update', $group->id), [
            'action' => 'update_quota',
            'storage_quota_gb' => 0,
        ])->assertSessionHasErrors('storage_quota_gb');

        $this->actingAs($platformAdmin)->patch(route('admin.groups.status.update', $group->id), [
            'action' => 'update_quota',
            'storage_quota_gb' => 50000,
        ])->assertSessionHasErrors('storage_quota_gb');

        // 3. Invalid mod version status produces validation error
        $this->actingAs($platformAdmin)->patch(route('admin.mod-versions.status.update', $modVersion), [
            'status' => 'hacked_status',
        ])->assertSessionHasErrors('status');

        // 4. SuperAdmin self-demotion and self-suspension blocked
        $this->actingAs($superAdmin)->patch(route('admin.users.status.update', $superAdmin), [
            'action' => 'update_role',
            'platform_role' => 'none',
        ])->assertSessionHasErrors('action');

        $this->actingAs($superAdmin)->patch(route('admin.users.status.update', $superAdmin), [
            'action' => 'suspend',
            'reason' => 'Testing self lockout',
        ])->assertSessionHasErrors('action');

        // 5. Sensitive credentials (password hashes, remember tokens) are never leaked in CSV or views
        $usersCsv = $this->actingAs($superAdmin)->get(route('admin.reports.export', 'users'))->streamedContent();
        $this->assertStringNotContainsString('$2y$', $usersCsv);
        $this->assertStringNotContainsString($superAdmin->password, $usersCsv);

        $userShowHtml = $this->actingAs($superAdmin)->get(route('admin.users.show', $superAdmin))->getContent();
        $this->assertStringNotContainsString('$2y$', $userShowHtml);
        $this->assertStringNotContainsString($superAdmin->password, $userShowHtml);

        // 6. Non-admin mutating attempts on admin routes are blocked with 403 Forbidden
        $this->actingAs($ordinaryUser)->patch(route('admin.groups.status.update', $group->id), [
            'action' => 'archive',
        ])->assertForbidden();

        $this->actingAs($ordinaryUser)->patch(route('admin.mod-versions.status.update', $modVersion), [
            'status' => 'revoked',
        ])->assertForbidden();
    }
}
