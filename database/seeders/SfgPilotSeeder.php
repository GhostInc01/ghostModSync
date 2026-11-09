<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\GroupRole;
use App\Enums\GroupVisibility;
use App\Enums\MembershipStatus;
use App\Enums\SyncPolicyMode;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\User;
use Illuminate\Database\Seeder;

class SfgPilotSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::query()->firstOrCreate(
            ['email' => 'admin@sfg-vtc.test'],
            [
                'name' => 'SFG Founder',
                'username' => 'sfg_founder',
                'email_verified_at' => now(),
                'password' => 'password',
            ]
        );
        $owner->forceFill(['is_platform_admin' => true])->save();

        $sfg = Group::query()->updateOrCreate(
            ['slug' => 'sfg'],
            [
                'owner_id' => $owner->id,
                'name' => 'SFG',
                'tag' => '[SFG]',
                'description' => 'Initial Pilot Virtual Trucking Company (SFG) for ETS2 and ATS convoy mod synchronisation.',
                'visibility' => GroupVisibility::Public,
                'sync_policy' => SyncPolicyMode::Automatic,
                'require_join_approval' => true,
                'require_mod_approval' => true,
                'storage_quota_bytes' => 107374182400, // 100 GB
            ]
        );

        GroupMembership::query()->updateOrCreate(
            [
                'group_id' => $sfg->id,
                'user_id' => $owner->id,
            ],
            [
                'role' => GroupRole::Owner,
                'status' => MembershipStatus::Active,
                'joined_at' => now(),
            ]
        );
    }
}
