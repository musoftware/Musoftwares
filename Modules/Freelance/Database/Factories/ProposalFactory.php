<?php

namespace Modules\Freelance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Freelance\Models\Proposal;
use Modules\Freelance\Models\Job;
use App\Models\User;

class ProposalFactory extends Factory
{
    protected $model = Proposal::class;

    public function definition(): array
    {
        return [
            'job_id' => Job::factory(),
            'freelancer_id' => User::factory(),
            'cover_letter' => $this->faker->paragraph,
            'bid_amount' => $this->faker->randomFloat(2, 50, 1000),
            'currency_id' => 1,
            'proposed_budget_points' => $this->faker->randomNumber(3),
            'points_spent' => 20,
            'status' => 'pending',
        ];
    }
}
