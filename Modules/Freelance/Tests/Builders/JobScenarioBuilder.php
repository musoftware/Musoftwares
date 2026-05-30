<?php



namespace Modules\Freelance\Tests\Builders;

use Modules\Freelance\Models\Job;
use App\Models\User;


class JobScenarioBuilder
{
    protected $client;
    protected $freelancers = [];
    protected $job;
    protected $jobCost = 25;
    protected $proposalCost = 2;

    public static function create(): self
    {
        return new self();
    }

    public function withClient(int $points = 100, float $balance = 1000.0)
    {
        $this->client = User::factory()->create([
            'points_balance' => $points,
            'user_balance' => $balance
        ]);
        return $this;
    }

    public function withFreelancers(int $count, int $points = 50)
    {
        $this->freelancers = User::factory()->count($count)->create([
            'points_balance' => $points,
        ]);
        return $this;
    }

    public function withJob(array $attributes = [])
    {
        $this->job = Job::create(array_merge([
            'client_id' => $this->client->id,
            'title' => 'Enterprise System Architecture',
            'description' => 'Build a robust freelance platform.',
            'budget_points' => 5000,
            'min_proposal_points' => 0,
            'type' => 'fixed',
            'duration' => '3_months',
            'status' => 'open',
        ], $attributes));

        return $this;
    }

    public function getClient(): User
    {
        return $this->client;
    }

    public function getFreelancer(int $index = 0): User
    {
        return $this->freelancers[$index];
    }

    public function getFreelancers()
    {
        return $this->freelancers;
    }

    public function getJob(): Job
    {
        return $this->job;
    }
}
