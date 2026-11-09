<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\DependencyType;
use App\Enums\GameCode;
use App\Enums\GroupRole;
use App\Enums\GroupVisibility;
use App\Enums\JoinRequestStatus;
use App\Enums\MembershipStatus;
use App\Enums\ModApprovalStatus;
use App\Enums\ModSourceType;
use App\Enums\SyncItemAction;
use App\Enums\SyncPolicyMode;
use App\Enums\SyncStatus;
use App\Models\ConvoyProfile;
use App\Models\ConvoyProfileRevision;
use App\Models\Device;
use App\Models\Group;
use App\Models\GroupJoinRequest;
use App\Models\GroupMembership;
use App\Models\Mod;
use App\Models\ModVersion;
use App\Models\SyncApproval;
use App\Models\User;
use App\Services\Groups\GroupManagementService;
use App\Services\Mods\ModCatalogueService;
use Database\Seeders\GameSeeder;
use Database\Seeders\SfgPilotSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ModAndConvoySyncTest extends TestCase
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

    public function test_mod_manager_can_register_s3_and_steam_workshop_mods_and_build_convoy_profile(): void
    {
        Http::fake([
            'api.steampowered.com/*' => Http::response([
                'response' => [
                    'publishedfiledetails' => [
                        [
                            'result' => 1,
                            'title' => 'SiSL Mega Pack',
                            'file_size' => 154000000,
                            'time_updated' => 1730000000,
                        ],
                    ],
                ],
            ]),
        ]);

        $sfg = Group::query()->where('slug', 'sfg')->firstOrFail();
        $modManager = User::factory()->create();

        GroupMembership::query()->create([
            'group_id' => $sfg->id,
            'user_id' => $modManager->id,
            'role' => GroupRole::ModManager,
            'status' => MembershipStatus::Active,
            'joined_at' => now(),
        ]);

        // 1. Create S3 Mod
        $this->actingAs($modManager)->post(route('groups.mods.store', $sfg), [
            'game_code' => GameCode::Ets2->value,
            'name' => 'SFG Official Livery Pack',
            'category' => 'skin',
            'author_name' => 'SFG Design Team',
            'source_type' => ModSourceType::S3Upload->value,
        ])->assertRedirect(route('groups.show', $sfg));

        $s3Mod = Mod::query()->where('slug', 'sfg-official-livery-pack')->firstOrFail();
        $sha256 = str_repeat('a', 64);

        // 2. Upload S3 ModVersion
        $this->actingAs($modManager)->post(route('groups.mods.versions.store', [$sfg, $s3Mod]), [
            'version_number' => '1.2.0',
            'min_game_version' => '1.53',
            'max_game_version' => '1.53',
            'archive_filename' => 'sfg_livery_v1_2_0.scs',
            'file_size_bytes' => 52428800,
            'sha256_hash' => $sha256,
            'changelog' => 'Updated 2026 trailer skins.',
        ])->assertRedirect(route('groups.show', $sfg))
            ->assertSessionHas('upload_instructions');

        $s3Version = ModVersion::query()->where('mod_id', $s3Mod->id)->firstOrFail();
        $this->assertSame($sha256, $s3Version->sha256_hash);
        $this->assertSame('s3_upload', $s3Version->source->source_type->value);

        // 3. Create Steam Workshop Mod
        $this->actingAs($modManager)->post(route('groups.mods.store', $sfg), [
            'game_code' => GameCode::Ets2->value,
            'name' => 'SiSL Mega Pack',
            'category' => 'interior',
            'source_type' => ModSourceType::SteamWorkshop->value,
        ])->assertRedirect(route('groups.show', $sfg));

        $steamMod = Mod::query()->where('slug', 'sisl-mega-pack')->firstOrFail();

        $this->actingAs($modManager)->post(route('groups.mods.versions.store', [$sfg, $steamMod]), [
            'version_number' => '3.4',
            'archive_filename' => 'sisl_mega_pack.zip',
            'steam_published_file_id' => '645553204',
        ])->assertRedirect(route('groups.show', $sfg));

        $steamVersion = ModVersion::query()->where('mod_id', $steamMod->id)->firstOrFail();
        $this->assertSame(154000000, $steamVersion->file_size_bytes);
        $this->assertSame('645553204', $steamVersion->source->steam_published_file_id);

        // 4. Add Dependency: SFG Official Livery Pack requires SiSL Mega Pack
        $this->actingAs($modManager)->post(
            route('groups.mods.versions.dependencies.store', [$sfg, $s3Mod, $s3Version]),
            [
                'related_mod_id' => $steamMod->id,
                'dependency_type' => DependencyType::Requires->value,
            ]
        )->assertRedirect(route('groups.show', $sfg));

        // 5. Attempting to create a ConvoyProfile with ONLY SFG Livery Pack (missing required SiSL Mega Pack) fails validation
        $this->actingAs($modManager)->post(route('groups.profiles.store', $sfg), [
            'game_code' => GameCode::Ets2->value,
            'name' => 'SFG Sunday Convoy',
            'target_game_version' => '1.53',
            'mods' => [
                ['mod_version_id' => $s3Version->id, 'is_required' => true],
            ],
        ])->assertSessionHasErrors('mods');

        // 6. Creating ConvoyProfile with both mods succeeds and computes deterministic manifest_hash
        $this->actingAs($modManager)->post(route('groups.profiles.store', $sfg), [
            'game_code' => GameCode::Ets2->value,
            'name' => 'SFG Sunday Convoy',
            'target_game_version' => '1.53',
            'is_default' => true,
            'mods' => [
                ['mod_version_id' => $steamVersion->id, 'is_required' => true],
                ['mod_version_id' => $s3Version->id, 'is_required' => true],
            ],
        ])->assertSessionHasNoErrors()
            ->assertRedirect(route('groups.show', $sfg));

        $profile = ConvoyProfile::query()->where('slug', 'sfg-sunday-convoy')->firstOrFail();
        $this->assertSame(1, $profile->revision);
        $this->assertSame(64, strlen($profile->manifest_hash));
        $this->assertCount(2, $profile->profileMods);

        // 7. Reordering mods updates manifest_hash and increments revision to 2
        $initialHash = $profile->manifest_hash;

        $this->actingAs($modManager)->put(route('groups.profiles.mods.sync', [$sfg, $profile]), [
            'mods' => [
                ['mod_version_id' => $s3Version->id, 'is_required' => true],
                ['mod_version_id' => $steamVersion->id, 'is_required' => true],
            ],
        ])->assertSessionHasNoErrors();

        $profile->refresh();
        $this->assertSame(2, $profile->revision);
        $this->assertNotSame($initialHash, $profile->manifest_hash);
    }

    public function test_circular_mod_dependencies_are_rejected(): void
    {
        $sfg = Group::query()->where('slug', 'sfg')->firstOrFail();
        $owner = $sfg->owner;

        $this->actingAs($owner)->post(route('groups.mods.store', $sfg), [
            'game_code' => GameCode::Ats->value,
            'name' => 'Mod Alpha',
            'source_type' => ModSourceType::S3Upload->value,
        ]);
        $modA = Mod::query()->where('slug', 'mod-alpha')->firstOrFail();

        $this->actingAs($owner)->post(route('groups.mods.versions.store', [$sfg, $modA]), [
            'version_number' => '1.0',
            'archive_filename' => 'mod_alpha.scs',
            'file_size_bytes' => 1024,
            'sha256_hash' => str_repeat('1', 64),
        ]);
        $verA = $modA->versions()->firstOrFail();

        $this->actingAs($owner)->post(route('groups.mods.store', $sfg), [
            'game_code' => GameCode::Ats->value,
            'name' => 'Mod Beta',
            'source_type' => ModSourceType::S3Upload->value,
        ]);
        $modB = Mod::query()->where('slug', 'mod-beta')->firstOrFail();

        $this->actingAs($owner)->post(route('groups.mods.versions.store', [$sfg, $modB]), [
            'version_number' => '1.0',
            'archive_filename' => 'mod_beta.scs',
            'file_size_bytes' => 1024,
            'sha256_hash' => str_repeat('2', 64),
        ]);
        $verB = $modB->versions()->firstOrFail();

        // Alpha requires Beta (valid)
        $this->actingAs($owner)->post(
            route('groups.mods.versions.dependencies.store', [$sfg, $modA, $verA]),
            [
                'related_mod_id' => $modB->id,
                'dependency_type' => DependencyType::Requires->value,
            ]
        )->assertSessionHasNoErrors();

        // Beta requires Alpha (circular -> rejected)
        $this->actingAs($owner)->post(
            route('groups.mods.versions.dependencies.store', [$sfg, $modB, $verB]),
            [
                'related_mod_id' => $modA->id,
                'dependency_type' => DependencyType::Requires->value,
            ]
        )->assertSessionHasErrors('related_mod_id');
    }

    public function test_desktop_client_sync_workflow_enforces_approval_mode_and_reports_sync_session(): void
    {
        $owner = User::factory()->create();
        $driver = User::factory()->create();

        $group = Group::factory()->create([
            'owner_id' => $owner->id,
            'slug' => 'elite-vtc',
            'visibility' => GroupVisibility::Private,
            'sync_policy' => SyncPolicyMode::ApprovalRequired,
        ]);

        GroupMembership::query()->create([
            'group_id' => $group->id,
            'user_id' => $owner->id,
            'role' => GroupRole::Owner,
            'status' => MembershipStatus::Active,
            'joined_at' => now(),
        ]);

        GroupMembership::query()->create([
            'group_id' => $group->id,
            'user_id' => $driver->id,
            'role' => GroupRole::Member,
            'status' => MembershipStatus::Active,
            'joined_at' => now(),
        ]);

        // Create S3 Mod & Convoy Profile
        $this->actingAs($owner)->post(route('groups.mods.store', $group), [
            'game_code' => GameCode::Ets2->value,
            'name' => 'Elite Physics Mod',
            'source_type' => ModSourceType::S3Upload->value,
        ]);
        $mod = Mod::query()->where('group_id', $group->id)->firstOrFail();

        $this->actingAs($owner)->post(route('groups.mods.versions.store', [$group, $mod]), [
            'version_number' => '2.0.0',
            'archive_filename' => 'elite_physics_2.scs',
            'file_size_bytes' => 4096000,
            'sha256_hash' => str_repeat('b', 64),
        ]);
        $version = $mod->versions()->firstOrFail();

        $this->actingAs($owner)->post(route('groups.profiles.store', $group), [
            'game_code' => GameCode::Ets2->value,
            'name' => 'Elite Convoy Profile',
            'target_game_version' => '1.53',
            'mods' => [
                ['mod_version_id' => $version->id, 'is_required' => true],
            ],
        ]);
        $profile = ConvoyProfile::query()->where('group_id', $group->id)->firstOrFail();

        // Register Driver's Windows Desktop Device
        $device = Device::factory()->create([
            'user_id' => $driver->id,
        ]);

        Sanctum::actingAs($driver, ['groups:read', 'profiles:read', 'mods:download', 'sync:report']);

        // 1. Driver fetches manifest via API
        $manifestResponse = $this->getJson("/api/v1/groups/{$group->slug}/profiles/{$profile->slug}/manifest");
        $manifestResponse->assertOk()
            ->assertJsonPath('data.sync_policy', 'approval_required')
            ->assertJsonPath('data.manifest_hash', $profile->manifest_hash)
            ->assertJsonPath('data.mods.0.archive_filename', 'elite_physics_2.scs')
            ->assertJsonPath('data.mods.0.sha256_hash', str_repeat('b', 64));

        // 2. Driver attempts to request S3 download URL before approval -> 403 Forbidden
        $this->postJson("/api/v1/profiles/{$profile->id}/mods/{$version->id}/download-url")
            ->assertForbidden();

        // 3. Driver submits a sync approval request from desktop app
        $approvalRes = $this->postJson("/api/v1/profiles/{$profile->id}/sync-requests", [
            'device_uuid' => $device->device_uuid,
        ]);
        $approvalRes->assertStatus(202)
            ->assertJsonPath('status', 'pending');

        $approval = SyncApproval::query()->firstOrFail();

        // 4. Group Owner approves the sync request via Web UI
        $this->actingAs($owner)->patch(
            route('groups.profiles.approvals.review', [$group, $profile, $approval]),
            ['status' => 'approved']
        )->assertRedirect(route('groups.show', $group));

        // 5. Driver now requests S3 pre-signed download URL -> 200 OK
        Sanctum::actingAs($driver, ['groups:read', 'profiles:read', 'mods:download', 'sync:report']);

        $downloadRes = $this->postJson("/api/v1/profiles/{$profile->id}/mods/{$version->id}/download-url");
        $downloadRes->assertOk()
            ->assertJsonStructure(['download_url', 'expires_at', 'sha256_hash', 'file_size_bytes', 'archive_filename'])
            ->assertJsonPath('sha256_hash', str_repeat('b', 64));

        // 6. Driver reports sync completion to /api/v1/profiles/{id}/sync-sessions
        $syncReport = $this->postJson("/api/v1/profiles/{$profile->id}/sync-sessions", [
            'device_uuid' => $device->device_uuid,
            'reported_manifest_hash' => $profile->manifest_hash,
            'status' => SyncStatus::InSync->value,
            'missing_mods_count' => 0,
            'mismatched_mods_count' => 0,
            'extra_local_mods_count' => 0,
        ]);

        $syncReport->assertOk()
            ->assertJsonPath('status', 'in_sync');

        $this->assertDatabaseHas('sync_sessions', [
            'convoy_profile_id' => $profile->id,
            'device_id' => $device->id,
            'status' => 'in_sync',
        ]);
    }

    public function test_dlc_requirements_group_mod_approvals_immutable_profile_revisions_and_sync_session_items(): void
    {
        $sfg = Group::query()->where('slug', 'sfg')->firstOrFail();
        $owner = $sfg->owner;

        // 1. Create a Mod Version with required DLCs (dlc_north, dlc_balkan_w)
        $this->actingAs($owner)->post(route('groups.mods.store', $sfg), [
            'game_code' => GameCode::Ets2->value,
            'name' => 'ProMods Europe',
            'category' => 'map',
            'source_type' => ModSourceType::S3Upload->value,
        ])->assertRedirect(route('groups.show', $sfg));

        $proMods = Mod::query()->where('slug', 'promods-europe')->firstOrFail();

        $this->actingAs($owner)->post(route('groups.mods.versions.store', [$sfg, $proMods]), [
            'version_number' => '2.72',
            'min_game_version' => '1.53',
            'max_game_version' => '1.53',
            'archive_filename' => 'promods_v272.scs',
            'file_size_bytes' => 104857600,
            'sha256_hash' => str_repeat('c', 64),
            'required_dlc_codes' => ['dlc_north', 'dlc_balkan_w'],
        ])->assertRedirect(route('groups.show', $sfg));

        $proModsV272 = $proMods->versions()->firstOrFail();
        $this->assertCount(2, $proModsV272->requiredDlcs);

        // 2. Verify group_mod_approvals: auto-approved on creation, but if revoked, ConvoyProfile rejects it
        $this->assertDatabaseHas('group_mod_approvals', [
            'group_id' => $sfg->id,
            'mod_version_id' => $proModsV272->id,
            'status' => ModApprovalStatus::Approved->value,
        ]);

        /** @var ModCatalogueService $modCatalogueService */
        $modCatalogueService = app(ModCatalogueService::class);
        $modCatalogueService->updateGroupModApproval(
            group: $sfg,
            version: $proModsV272,
            reviewer: $owner,
            status: ModApprovalStatus::Revoked,
            notes: 'Temporarily revoked for testing.',
        );

        $this->actingAs($owner)->post(route('groups.profiles.store', $sfg), [
            'game_code' => GameCode::Ets2->value,
            'name' => 'SFG Euro Tour',
            'target_game_version' => '1.53',
            'mods' => [
                ['mod_version_id' => $proModsV272->id, 'is_required' => true],
            ],
        ])->assertSessionHasErrors('mods');

        // Re-approve the mod version
        $modCatalogueService->updateGroupModApproval(
            group: $sfg,
            version: $proModsV272,
            reviewer: $owner,
            status: ModApprovalStatus::Approved,
            notes: 'Approved for SFG Euro Tour.',
        );

        // 3. Create ConvoyProfile -> creates immutable Revision #1 snapshot
        $this->actingAs($owner)->post(route('groups.profiles.store', $sfg), [
            'game_code' => GameCode::Ets2->value,
            'name' => 'SFG Euro Tour',
            'target_game_version' => '1.53',
            'mods' => [
                ['mod_version_id' => $proModsV272->id, 'is_required' => true],
            ],
        ])->assertSessionHasNoErrors();

        $profile = ConvoyProfile::query()->where('slug', 'sfg-euro-tour')->firstOrFail();
        $rev1Id = $profile->current_revision_id;
        $this->assertNotNull($rev1Id);

        $rev1 = ConvoyProfileRevision::query()->findOrFail($rev1Id);
        $this->assertSame(1, $rev1->revision_number);
        $this->assertCount(1, $rev1->revisionMods);
        $this->assertSame('promods_v272.scs', $rev1->revisionMods->first()->modVersion->archive_filename);
        $this->assertSame('promods_v272.scs', $rev1->snapshot_json['mods'][0]['archive_filename']);

        // 4. Record a SyncSession + SyncSessionItem against Revision #1
        $device = Device::factory()->create(['user_id' => $owner->id]);
        Sanctum::actingAs($owner, ['groups:read', 'profiles:read', 'mods:download', 'sync:report']);

        // Verify manifest includes current_revision_id and required_dlcs
        $this->getJson("/api/v1/groups/{$sfg->slug}/profiles/{$profile->slug}/manifest")
            ->assertOk()
            ->assertJsonPath('data.current_revision_id', $rev1Id)
            ->assertJsonCount(2, 'data.mods.0.required_dlcs');

        $session1Res = $this->postJson("/api/v1/profiles/{$profile->id}/sync-sessions", [
            'device_uuid' => $device->device_uuid,
            'reported_manifest_hash' => $profile->manifest_hash,
            'status' => SyncStatus::InSync->value,
            'bytes_downloaded' => 104857600,
            'duration_ms' => 4200,
            'items' => [
                [
                    'mod_version_id' => $proModsV272->id,
                    'action_taken' => SyncItemAction::DownloadedS3->value,
                    'status' => SyncStatus::InSync->value,
                    'local_sha256_hash' => str_repeat('c', 64),
                    'bytes_downloaded' => 104857600,
                ],
            ],
        ])->assertOk();

        $session1Id = $session1Res->json('session_id');
        $this->assertDatabaseHas('sync_session_items', [
            'sync_session_id' => $session1Id,
            'mod_version_id' => $proModsV272->id,
            'action_taken' => 'downloaded_s3',
        ]);

        // 5. Publish a second mod version (2.73) and update the ConvoyProfile -> creates Revision #2
        $this->actingAs($owner)->post(route('groups.mods.versions.store', [$sfg, $proMods]), [
            'version_number' => '2.73',
            'archive_filename' => 'promods_v273.scs',
            'file_size_bytes' => 109000000,
            'sha256_hash' => str_repeat('d', 64),
        ]);
        $proModsV273 = $proMods->versions()->where('version_number', '2.73')->firstOrFail();

        $this->actingAs($owner)->put(route('groups.profiles.mods.sync', [$sfg, $profile]), [
            'mods' => [
                ['mod_version_id' => $proModsV273->id, 'is_required' => true],
            ],
        ])->assertSessionHasNoErrors();

        $profile->refresh();
        $this->assertSame(2, $profile->revision);
        $this->assertNotSame($rev1Id, $profile->current_revision_id);

        // Verify Revision #1 snapshot is completely preserved and Session #1 still references Revision #1
        $rev1->refresh();
        $this->assertSame('promods_v272.scs', $rev1->revisionMods()->with('modVersion')->firstOrFail()->modVersion->archive_filename);
        $this->assertSame('promods_v272.scs', $rev1->snapshot_json['mods'][0]['archive_filename']);
        $this->assertDatabaseHas('sync_sessions', [
            'id' => $session1Id,
            'convoy_profile_revision_id' => $rev1Id,
            'profile_revision' => 1,
        ]);

        // 6. Verify group_join_requests workflow
        $applicant = User::factory()->create();
        /** @var GroupManagementService $groupService */
        $groupService = app(GroupManagementService::class);
        $groupService->requestJoinPublicGroup($sfg, $applicant, 'I drive ETS2 every weekend!');

        $joinRequest = GroupJoinRequest::query()
            ->where('group_id', $sfg->id)
            ->where('user_id', $applicant->id)
            ->firstOrFail();
        $this->assertSame(JoinRequestStatus::Pending, $joinRequest->status);

        $groupService->reviewJoinRequest($joinRequest, $owner, JoinRequestStatus::Approved, 'Welcome to SFG!');
        $this->assertDatabaseHas('group_join_requests', [
            'id' => $joinRequest->id,
            'status' => JoinRequestStatus::Approved->value,
            'reviewed_by' => $owner->id,
        ]);
        $this->assertDatabaseHas('group_memberships', [
            'group_id' => $sfg->id,
            'user_id' => $applicant->id,
            'status' => MembershipStatus::Active->value,
        ]);
    }
}
