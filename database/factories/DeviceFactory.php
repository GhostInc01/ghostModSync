<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Device;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Device>
 */
class DeviceFactory extends Factory
{
    protected $model = Device::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'personal_access_token_id' => null,
            'device_uuid' => fake()->uuid(),
            'device_name' => 'DESKTOP-'.strtoupper(fake()->bothify('??###')),
            'os_version' => 'Windows 11 23H2',
            'client_version' => '1.0.0',
            'ets2_mod_path' => 'C:\\Users\\Driver\\Documents\\Euro Truck Simulator 2\\mod',
            'ats_mod_path' => 'C:\\Users\\Driver\\Documents\\American Truck Simulator\\mod',
            'last_seen_at' => now(),
            'revoked_at' => null,
        ];
    }

    public function revoked(): static
    {
        return $this->state(fn () => [
            'revoked_at' => now(),
        ]);
    }
}
