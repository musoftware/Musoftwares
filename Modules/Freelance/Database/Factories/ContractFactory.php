<?php

namespace Modules\Freelance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Freelance\Models\Contract;
use Modules\Freelance\Models\Job;
use Modules\Freelance\Models\Proposal;
use App\Models\User;

class ContractFactory extends Factory
{
    protected $model = Contract::class;

    public function definition(): array
    {
        return [
            'job_id' => Job::factory(),
            'proposal_id' => Proposal::factory(),
            'client_id' => User::factory(),
            'freelancer_id' => User::factory(),
            'amount' => $this->faker->randomFloat(2, 50, 1000),
            'currency_id' => 1,
            'contract_points' => 10,
            'status' => 'active',
            'started_at' => now(),
            'completed_at' => null,
        ];
    }
}
