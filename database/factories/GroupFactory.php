<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\GroupVisibility;
use App\Enums\SyncPolicyMode;
use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Group>
 */
class GroupFactory extends Factory
{
    protected $model = Group::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company().' VTC';

        return [
            'owner_id' => User::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('###'),
            'tag' => '['.strtoupper(fake()->lexify('???')).']',
            'description' => fake()->sentence(12),
            'visibility' => GroupVisibility::Private,
            'sync_policy' => SyncPolicyMode::Automatic,
            'require_join_approval' => true,
            'require_mod_approval' => true,
            'storage_quota_bytes' => 53687091200,
        ];
    }

    public function public(): static
    {
        return $this->state(fn () => [
            'visibility' => GroupVisibility::Public,
        ]);
    }

    public function approvalRequired(): static
    {
        return $this->state(fn () => [
            'sync_policy' => SyncPolicyMode::ApprovalRequired,
        ]);
    }
}
