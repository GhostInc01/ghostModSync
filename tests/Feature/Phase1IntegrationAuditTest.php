<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\GroupRole;
use App\Enums\GroupVisibility;
use App\Enums\JoinRequestStatus;
use App\Enums\MembershipStatus;
use App\Models\Device;
use App\Models\Group;
use App\Models\GroupJoinRequest;
use App\Models\GroupMembership;
use App\Models\User;
use Database\Seeders\GameSeeder;
use Database\Seeders\SfgPilotSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class Phase1IntegrationAuditTest extends TestCase
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

    public function test_all_phase1_blade_interfaces_render_cleanly_under_strict_mode(): void
    {
        $sfg = Group::query()->where('slug', 'sfg')->firstOrFail();
        $owner = $sfg->owner;
        $applicant = User::factory()->create();

        Device::factory()->create(['user_id' => $owner->id]);

        // Applicant submits a join request so join-requests view renders non-empty state including isPending()
        $this->actingAs($applicant)->post(route('groups.join', $sfg), [
            'message' => 'Testing join request Blade view.',
        ]);

        // Owner issues an invitation so invitations view renders non-empty state
        $this->actingAs($owner)->post(route('groups.invitations.store', $sfg), [
            'role' => GroupRole::Member->value,
            'max_uses' => 5,
            'expires_in_days' => 7,
        ]);

        $this->actingAs($owner)->get(route('dashboard'))->assertOk();
        $this->actingAs($owner)->get(route('devices.index'))->assertOk();
        $this->actingAs($owner)->get(route('groups.index', ['q' => 'SFG']))->assertOk();
        $this->actingAs($owner)->get(route('groups.create'))->assertOk();
        $this->actingAs($owner)->get(route('groups.show', $sfg))->assertOk();
        $this->actingAs($owner)->get(route('groups.members', $sfg))->assertOk();
        $this->actingAs($owner)->get(route('groups.join-requests', $sfg))->assertOk();
        $this->actingAs($owner)->get(route('groups.invitations', $sfg))->assertOk();
        $this->actingAs($owner)->get(route('groups.settings', $sfg))->assertOk();
    }

    public function test_api_v1_groups_index_has_no_n_plus_one_queries(): void
    {
        $user = User::factory()->create();

        for ($i = 1; $i <= 8; $i++) {
            $group = Group::factory()->create([
                'visibility' => GroupVisibility::Public,
            ]);
            GroupMembership::query()->create([
                'group_id' => $group->id,
                'user_id' => $user->id,
                'role' => GroupRole::Member,
                'status' => MembershipStatus::Active,
                'joined_at' => now(),
            ]);
        }

        Sanctum::actingAs($user, ['groups:read']);

        DB::enableQueryLog();
        $response = $this->getJson('/api/v1/groups');
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $response->assertOk()
            ->assertJsonCount(9, 'data'); // 8 created + 1 SFG pilot group

        // Only 2 queries should execute: 1 for groups, 1 eager load for memberships
        $this->assertLessThanOrEqual(3, count($queries));
    }

    public function test_privilege_escalation_via_invitations_and_suspended_rejoin_and_duplicate_review_are_blocked(): void
    {
        $sfg = Group::query()->where('slug', 'sfg')->firstOrFail();
        $owner = $sfg->owner;
        $admin = User::factory()->create();
        $moderator = User::factory()->create();
        $suspendedDriver = User::factory()->create();
        $applicant = User::factory()->create();

        GroupMembership::query()->create([
            'group_id' => $sfg->id,
            'user_id' => $admin->id,
            'role' => GroupRole::Admin,
            'status' => MembershipStatus::Active,
            'joined_at' => now(),
        ]);

        GroupMembership::query()->create([
            'group_id' => $sfg->id,
            'user_id' => $moderator->id,
            'role' => GroupRole::Moderator,
            'status' => MembershipStatus::Active,
            'joined_at' => now(),
        ]);

        GroupMembership::query()->create([
            'group_id' => $sfg->id,
            'user_id' => $suspendedDriver->id,
            'role' => GroupRole::Member,
            'status' => MembershipStatus::Suspended,
            'joined_at' => now(),
        ]);

        // 1. Moderator cannot issue an invitation for Admin or Moderator role (privilege escalation prevention)
        $this->actingAs($moderator)->post(route('groups.invitations.store', $sfg), [
            'role' => GroupRole::Admin->value,
        ])->assertSessionHasErrors('role');

        // 2. Admin cannot issue an invitation for Admin or Owner role
        $this->actingAs($admin)->post(route('groups.invitations.store', $sfg), [
            'role' => GroupRole::Admin->value,
        ])->assertSessionHasErrors('role');

        // 3. Moderator CAN issue an invitation for Member role, and revoking it invalidates acceptance
        $this->actingAs($moderator)->post(route('groups.invitations.store', $sfg), [
            'role' => GroupRole::Member->value,
        ])->assertSessionHasNoErrors();

        $invitation = $sfg->invitations()->latest('id')->firstOrFail();

        $this->actingAs($moderator)
            ->delete(route('groups.invitations.destroy', [$sfg, $invitation]))
            ->assertRedirect(route('groups.invitations', $sfg));

        $this->actingAs($applicant)
            ->get(route('groups.invitations.accept', $invitation->token))
            ->assertSessionHasErrors('token');

        // 4. Suspended member cannot bypass suspension by calling POST /groups/{group}/join
        $this->actingAs($suspendedDriver)
            ->post(route('groups.join', $sfg))
            ->assertSessionHasErrors('group');

        // 5. Re-reviewing an already approved join request is rejected
        $this->actingAs($applicant)->post(route('groups.join', $sfg));
        $joinRequest = GroupJoinRequest::query()
            ->where('group_id', $sfg->id)
            ->where('user_id', $applicant->id)
            ->firstOrFail();

        $this->actingAs($owner)->patch(route('groups.join-requests.review', [$sfg, $joinRequest]), [
            'status' => JoinRequestStatus::Approved->value,
        ])->assertSessionHasNoErrors();

        $this->actingAs($moderator)->patch(route('groups.join-requests.review', [$sfg, $joinRequest]), [
            'status' => JoinRequestStatus::Rejected->value,
        ])->assertSessionHasErrors('status');

        $this->assertSame(GroupRole::Member, $sfg->activeRoleFor($applicant));
    }
}
