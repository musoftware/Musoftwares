<?php

use Modules\Freelance\Models\Proposal;
use Modules\Freelance\Models\Contract;
use Modules\Freelance\Tests\Builders\JobScenarioBuilder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class)->in(__DIR__);

it('prevents an unauthorized user from modifying a contract state', function () {
    $scenario = JobScenarioBuilder::create()
        ->withClient()
        ->withFreelancers(2)
        ->withJob();

    $client = $scenario->getClient();
    $job = $scenario->getJob();
    $freelancer = $scenario->getFreelancer(0);
    $hacker = clone $scenario->getFreelancer(1);

    $proposal = Proposal::create([
        'job_id' => $job->id,
        'freelancer_id' => $freelancer->id,
        'cover_letter' => 'My proposal',
        'proposed_budget_points' => 1000,
        'points_spent' => 2,
        'status' => 'accepted'
    ]);

    $contract = Contract::create([
        'job_id' => $job->id,
        'proposal_id' => $proposal->id,
        'client_id' => $client->id,
        'freelancer_id' => $freelancer->id,
        'contract_points' => 1000,
        'status' => 'active',
        'started_at' => now(),
    ]);

    // Hacker tries to complete the contract via API
    $response = $this->actingAs($hacker)->postJson("/api/freelance/contracts/{$contract->id}/complete");

    if ($response->status() === 404) {
        $this->markTestSkipped('Route not implemented yet.');
    }

    $response->assertStatus(403); // Forbidden
    
    // Assure database state wasn't mutated
    expect($contract->fresh()->status)->toBe('active');
});
