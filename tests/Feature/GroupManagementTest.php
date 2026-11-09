<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\GroupRole;
use App\Enums\GroupVisibility;
use App\Enums\JoinRequestStatus;
use App\Enums\MembershipStatus;
use App\Enums\SyncPolicyMode;
use App\Models\Group;
use App\Models\GroupJoinRequest;
use App\Models\GroupMembership;
use App\Models\User;
use Database\Seeders\SfgPilotSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GroupManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_sfg_pilot_seeder_provisions_sfg_group_and_owner(): void
    {
        $this->seed(SfgPilotSeeder::class);

        $this->assertDatabaseHas('groups', [
            'slug' => 'sfg',
            'name' => 'SFG',
            'tag' => '[SFG]',
            'visibility' => GroupVisibility::Public->value,
        ]);

        $sfg = Group::query()->where('slug', 'sfg')->firstOrFail();
        $this->assertSame(GroupRole::Owner, $sfg->activeRoleFor($sfg->owner));
    }

    public function test_user_can_belong_to_multiple_groups_with_different_roles(): void
    {
        $user = User::factory()->create();
        $otherOwner = User::factory()->create();

        // User creates Group A (becomes Owner)
        $response = $this->actingAs($user)->post(route('groups.store'), [
            'name' => 'Alpha Logistics VTC',
            'slug' => 'alpha-logistics',
            'tag' => '[ALPHA]',
            'visibility' => GroupVisibility::Public->value,
            'sync_policy' => SyncPolicyMode::Automatic->value,
            'require_join_approval' => true,
        ]);

        $alpha = Group::query()->where('slug', 'alpha-logistics')->firstOrFail();
        $response->assertRedirect(route('groups.show', $alpha));

        // User is added to Group B as Mod Manager
        $bravo = Group::factory()->create([
            'owner_id' => $otherOwner->id,
            'name' => 'Bravo Heavy Haul',
            'slug' => 'bravo-haul',
            'visibility' => GroupVisibility::Private,
            'sync_policy' => SyncPolicyMode::ApprovalRequired,
        ]);

        GroupMembership::query()->create([
            'group_id' => $bravo->id,
            'user_id' => $user->id,
            'role' => GroupRole::ModManager,
            'status' => MembershipStatus::Active,
            'joined_at' => now(),
        ]);

        $this->assertSame(GroupRole::Owner, $alpha->activeRoleFor($user));
        $this->assertSame(GroupRole::ModManager, $bravo->activeRoleFor($user));

        // In Alpha, user can manage group settings; in Bravo, ModManager cannot manage group settings
        $this->assertTrue($user->can('update', $alpha));
        $this->assertFalse($user->can('update', $bravo));
        $this->assertTrue($user->can('manageMods', $bravo));
    }

    public function test_private_group_is_isolated_from_non_members_on_web_and_api(): void
    {
        $owner = User::factory()->create();
        $outsider = User::factory()->create();

        $privateGroup = Group::factory()->create([
            'owner_id' => $owner->id,
            'slug' => 'secret-vtc',
            'visibility' => GroupVisibility::Private,
        ]);

        GroupMembership::query()->create([
            'group_id' => $privateGroup->id,
            'user_id' => $owner->id,
            'role' => GroupRole::Owner,
            'status' => MembershipStatus::Active,
            'joined_at' => now(),
        ]);

        // Outsider blocked on Web
        $this->actingAs($outsider)
            ->get(route('groups.show', $privateGroup))
            ->assertForbidden();

        // Outsider blocked on API
        Sanctum::actingAs($outsider, ['groups:read']);

        $this->getJson('/api/v1/groups/secret-vtc')
            ->assertForbidden();

        // Private group excluded from outsider's /api/v1/groups index
        $this->getJson('/api/v1/groups')
            ->assertOk()
            ->assertJsonMissing(['slug' => 'secret-vtc']);

        // Owner sees private group on API
        Sanctum::actingAs($owner, ['groups:read']);

        $this->getJson('/api/v1/groups/secret-vtc')
            ->assertOk()
            ->assertJsonPath('data.slug', 'secret-vtc')
            ->assertJsonPath('data.my_role', 'owner');
    }

    public function test_private_group_can_be_joined_via_valid_invitation_token(): void
    {
        $owner = User::factory()->create();
        $invitee = User::factory()->create(['email' => 'driver@sfg.test']);

        $privateGroup = Group::factory()->create([
            'owner_id' => $owner->id,
            'slug' => 'private-division',
            'visibility' => GroupVisibility::Private,
        ]);

        GroupMembership::query()->create([
            'group_id' => $privateGroup->id,
            'user_id' => $owner->id,
            'role' => GroupRole::Owner,
            'status' => MembershipStatus::Active,
            'joined_at' => now(),
        ]);

        $response = $this->actingAs($owner)->post(route('groups.invitations.store', $privateGroup), [
            'email' => 'driver@sfg.test',
            'role' => GroupRole::ConvoyDispatcher->value,
            'max_uses' => 1,
            'expires_in_days' => 7,
        ]);

        $response->assertRedirect(route('groups.show', $privateGroup));
        $invitation = $privateGroup->invitations()->firstOrFail();

        $this->actingAs($invitee)
            ->get(route('groups.invitations.accept', $invitation->token))
            ->assertRedirect(route('groups.show', $privateGroup));

        $this->assertSame(GroupRole::ConvoyDispatcher, $privateGroup->activeRoleFor($invitee));
    }

    public function test_role_hierarchy_suspend_restore_remove_transfer_ownership_and_archive_group(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $moderator = User::factory()->create();
        $member = User::factory()->create();
        $applicant = User::factory()->create();

        $group = Group::factory()->create([
            'owner_id' => $owner->id,
            'name' => 'Nordic Express VTC',
            'slug' => 'nordic-express',
            'visibility' => GroupVisibility::Public,
            'require_join_approval' => true,
        ]);

        GroupMembership::query()->create([
            'group_id' => $group->id,
            'user_id' => $owner->id,
            'role' => GroupRole::Owner,
            'status' => MembershipStatus::Active,
            'joined_at' => now(),
        ]);

        $adminMembership = GroupMembership::query()->create([
            'group_id' => $group->id,
            'user_id' => $admin->id,
            'role' => GroupRole::Admin,
            'status' => MembershipStatus::Active,
            'joined_at' => now(),
        ]);

        $moderatorMembership = GroupMembership::query()->create([
            'group_id' => $group->id,
            'user_id' => $moderator->id,
            'role' => GroupRole::Moderator,
            'status' => MembershipStatus::Active,
            'joined_at' => now(),
        ]);

        $memberMembership = GroupMembership::query()->create([
            'group_id' => $group->id,
            'user_id' => $member->id,
            'role' => GroupRole::Member,
            'status' => MembershipStatus::Active,
            'joined_at' => now(),
        ]);

        // 1. Applicant requests to join approval-based public group
        $this->actingAs($applicant)->post(route('groups.join', $group), [
            'message' => 'Experienced ETS2 driver applying!',
        ])->assertRedirect(route('groups.show', $group));

        $joinRequest = GroupJoinRequest::query()
            ->where('group_id', $group->id)
            ->where('user_id', $applicant->id)
            ->firstOrFail();
        $this->assertSame(JoinRequestStatus::Pending, $joinRequest->status);

        // 2. Regular Member cannot view or review join requests -> 403 Forbidden
        $this->actingAs($member)
            ->get(route('groups.join-requests', $group))
            ->assertForbidden();

        $this->actingAs($member)
            ->patch(route('groups.join-requests.review', [$group, $joinRequest]), [
                'status' => JoinRequestStatus::Approved->value,
            ])->assertForbidden();

        // 3. Moderator CAN view and approve join requests
        $this->actingAs($moderator)
            ->patch(route('groups.join-requests.review', [$group, $joinRequest]), [
                'status' => JoinRequestStatus::Approved->value,
                'review_notes' => 'Approved by Moderator.',
            ])->assertRedirect(route('groups.join-requests', $group));

        $this->assertSame(GroupRole::Member, $group->activeRoleFor($applicant));

        // 4. Moderator can suspend & restore a regular Member, but CANNOT suspend an Admin or change roles
        $this->actingAs($moderator)
            ->patch(route('groups.memberships.update', [$group, $memberMembership]), [
                'status' => MembershipStatus::Suspended->value,
            ])->assertRedirect(route('groups.show', $group));

        $this->assertSame(MembershipStatus::Suspended, $memberMembership->fresh()?->status);
        $this->assertNull($group->activeRoleFor($member));

        // Moderator restores suspended Member
        $this->actingAs($moderator)
            ->patch(route('groups.memberships.update', [$group, $memberMembership]), [
                'status' => MembershipStatus::Active->value,
            ])->assertRedirect(route('groups.show', $group));

        $this->assertSame(MembershipStatus::Active, $memberMembership->fresh()?->status);

        // Moderator attempting to suspend Admin fails validation
        $this->actingAs($moderator)
            ->patch(route('groups.memberships.update', [$group, $adminMembership]), [
                'status' => MembershipStatus::Suspended->value,
            ])->assertSessionHasErrors('membership');

        // Moderator attempting to change group settings gets 403 Forbidden
        $this->actingAs($moderator)
            ->patch(route('groups.update', $group), [
                'name' => 'Hacked Name',
                'visibility' => GroupVisibility::Public->value,
                'sync_policy' => SyncPolicyMode::Automatic->value,
            ])->assertForbidden();

        // 5. Admin can update group settings, but CANNOT transfer ownership or delete the group
        $this->actingAs($admin)
            ->patch(route('groups.update', $group), [
                'name' => 'Nordic Express International',
                'tag' => '[NORD]',
                'description' => 'Updated description',
                'visibility' => GroupVisibility::Public->value,
                'sync_policy' => SyncPolicyMode::Automatic->value,
                'logo_path' => 'logos/nordic.png',
            ])->assertRedirect(route('groups.show', $group));

        $this->assertSame('Nordic Express International', $group->fresh()?->name);

        $this->actingAs($admin)
            ->post(route('groups.transfer-ownership', $group), [
                'new_owner_id' => $admin->id,
            ])->assertForbidden();

        $this->actingAs($admin)
            ->delete(route('groups.destroy', $group))
            ->assertForbidden();

        // 6. Owner transfers ownership to Admin -> Admin becomes Owner, previous Owner becomes Admin
        $this->actingAs($owner)
            ->post(route('groups.transfer-ownership', $group), [
                'new_owner_id' => $admin->id,
            ])->assertRedirect(route('groups.show', $group));

        $group->refresh();
        $this->assertSame($admin->id, $group->owner_id);
        $this->assertSame(GroupRole::Owner, $group->activeRoleFor($admin));
        $this->assertSame(GroupRole::Admin, $group->memberships()->where('user_id', $owner->id)->firstOrFail()->role);

        // 7. New Owner removes a member and archives (soft-deletes) the group
        $this->actingAs($admin)
            ->delete(route('groups.memberships.destroy', [$group, $moderatorMembership]))
            ->assertRedirect(route('groups.members', $group));

        $this->assertDatabaseMissing('group_memberships', ['id' => $moderatorMembership->id]);

        $this->actingAs($admin)
            ->delete(route('groups.destroy', $group))
            ->assertRedirect(route('groups.index'));

        $this->assertSoftDeleted('groups', ['id' => $group->id]);
    }

    public function test_cross_group_manipulated_ids_are_blocked(): void
    {
        $ownerA = User::factory()->create();
        $ownerB = User::factory()->create();
        $memberB = User::factory()->create();

        $groupA = Group::factory()->create(['owner_id' => $ownerA->id, 'slug' => 'group-a']);
        GroupMembership::query()->create([
            'group_id' => $groupA->id,
            'user_id' => $ownerA->id,
            'role' => GroupRole::Owner,
            'status' => MembershipStatus::Active,
            'joined_at' => now(),
        ]);

        $groupB = Group::factory()->create(['owner_id' => $ownerB->id, 'slug' => 'group-b']);
        GroupMembership::query()->create([
            'group_id' => $groupB->id,
            'user_id' => $ownerB->id,
            'role' => GroupRole::Owner,
            'status' => MembershipStatus::Active,
            'joined_at' => now(),
        ]);
        $membershipB = GroupMembership::query()->create([
            'group_id' => $groupB->id,
            'user_id' => $memberB->id,
            'role' => GroupRole::Member,
            'status' => MembershipStatus::Active,
            'joined_at' => now(),
        ]);

        // Owner of Group A attempts to modify or delete Group B's membership using Group A's URL -> 404 Not Found (Scoped Binding)
        $this->actingAs($ownerA)
            ->patch("/groups/{$groupA->slug}/memberships/{$membershipB->id}", [
                'status' => MembershipStatus::Suspended->value,
            ])->assertNotFound();

        $this->actingAs($ownerA)
            ->delete("/groups/{$groupA->slug}/memberships/{$membershipB->id}")
            ->assertNotFound();

        // Owner of Group A attempting to directly hit Group B's endpoint -> 403 Forbidden
        $this->actingAs($ownerA)
            ->delete(route('groups.memberships.destroy', [$groupB, $membershipB]))
            ->assertForbidden();

        $this->assertSame(MembershipStatus::Active, $membershipB->fresh()?->status);
    }
}
