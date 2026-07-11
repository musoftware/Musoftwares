<?php

namespace Database\Factories;

use App\Models\SerialSoftware;
use Illuminate\Database\Eloquent\Factories\Factory;

class SerialSoftwareFactory extends Factory
{
    protected $model = SerialSoftware::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word().'.exe',
            'default_status' => $this->faker->randomElement(['active', 'inactive']),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'default_status' => 'active',
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'default_status' => 'inactive',
        ]);
    }
}
