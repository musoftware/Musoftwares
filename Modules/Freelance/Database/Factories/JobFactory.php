<?php

namespace Modules\Freelance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Freelance\Models\Job;
use App\Models\User;

class JobFactory extends Factory
{
    protected $model = Job::class;

    public function definition(): array
    {
        return [
            'client_id' => User::factory(),
            'title' => $this->faker->jobTitle,
            'description' => $this->faker->paragraph,
            'budget' => $this->faker->randomFloat(2, 50, 1000),
            'currency_id' => 1,
            'min_proposal_points' => 2,
            'type' => 'fixed',
            'duration' => 'less_than_1_month',
            'status' => 'open',
        ];
    }
}
