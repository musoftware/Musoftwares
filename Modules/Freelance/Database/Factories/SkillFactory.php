<?php

namespace Modules\Freelance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Freelance\Models\Skill;
use App\Models\User;

class SkillFactory extends Factory
{
    protected $model = Skill::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word,
            'description' => $this->faker->sentence,
            'status' => 'approved',
            'created_by' => User::factory(),
        ];
    }
}
