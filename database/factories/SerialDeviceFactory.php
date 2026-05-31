<?php

namespace Database\Factories;

use App\Models\SerialDevice;
use App\Models\SerialSoftware;
use Illuminate\Database\Eloquent\Factories\Factory;

class SerialDeviceFactory extends Factory
{
    protected $model = SerialDevice::class;

    public function definition(): array
    {
        return [
            'serial_software_id' => SerialSoftware::factory(),
            'device_id' => $this->faker->unique()->uuid(),
            'status' => $this->faker->randomElement(['active', 'inactive', 'blocked']),
            'user_name' => $this->faker->userName(),
            'machine_name' => $this->faker->word() . '-PC',
        ];
    }

    public function active(): static
    {
        return $this->state(fn(array $attributes) => ['status' => 'active']);
    }

    public function inactive(): static
    {
        return $this->state(fn(array $attributes) => ['status' => 'inactive']);
    }

    public function blocked(): static
    {
        return $this->state(fn(array $attributes) => ['status' => 'blocked']);
    }
}
