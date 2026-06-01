<?php

uses(\Tests\TestCase::class, \Illuminate\Foundation\Testing\DatabaseTransactions::class);

use Modules\Freelance\Domains\Contract\Actions\CompleteContractAction;
use Modules\Freelance\Tests\Builders\JobScenarioBuilder;
use Modules\Freelance\Models\Proposal;
use Modules\Freelance\Models\Contract;
use Illuminate\Foundation\Testing\DatabaseTransactions;




it('completes a contract and releases escrow to freelancer', function () {
    $scenario = JobScenarioBuilder::create()
        ->withClient(points: 100, balance: 5000)
        ->withFreelancers(1, points: 50)
        ->withJob(['status' => 'in_progress']);

    $client = $scenario->getClient();
    $job = $scenario->getJob();
    $freelancer = $scenario->getFreelancer(0);

    $proposal = Proposal::create(['job_id' => $job->id, 'freelancer_id' => $freelancer->id, 'cover_letter' => 'A', 'proposed_budget_points' => 1000, 'points_spent' => 2, 'status' => 'accepted']);
    
    $contract = Contract::create([
        'job_id' => $job->id,
        'proposal_id' => $proposal->id,
        'client_id' => $client->id,
        'freelancer_id' => $freelancer->id,
        'contract_points' => 1000,
        'status' => 'active',
        'started_at' => now(),
    ]);

    // Assume freelancer starts with 0 balance for simple testing
    $freelancer->update(['user_balance' => 0]);

    $action = app(CompleteContractAction::class);
    $action->execute($contract, $client);

    expect($contract->fresh()->status)->toBe('completed')
        ->and($job->fresh()->status->getValue())->toBe('completed');
});

it('throws exception if non-client tries to complete contract', function () {
    $scenario = JobScenarioBuilder::create()
        ->withClient()
        ->withFreelancers(2)
        ->withJob();

    $job = $scenario->getJob();
    $freelancer = $scenario->getFreelancer(0);
    $unauthorizedUser = $scenario->getFreelancer(1);

    $proposal = Proposal::create(['job_id' => $job->id, 'freelancer_id' => $freelancer->id, 'cover_letter' => 'A', 'proposed_budget_points' => 1000, 'points_spent' => 2, 'status' => 'accepted']);
    
    $contract = Contract::create([
        'job_id' => $job->id,
        'proposal_id' => $proposal->id,
        'client_id' => $scenario->getClient()->id,
        'freelancer_id' => $freelancer->id,
        'contract_points' => 1000,
        'status' => 'active',
        'started_at' => now(),
    ]);

    $action = app(CompleteContractAction::class);
    $action->execute($contract, $unauthorizedUser);
})->throws(\Exception::class, 'Unauthorized to complete this contract.');

it('throws exception if contract is already completed', function () {
    $scenario = JobScenarioBuilder::create()
        ->withClient()
        ->withFreelancers(1)
        ->withJob();

    $client = $scenario->getClient();
    $job = $scenario->getJob();
    $freelancer = $scenario->getFreelancer(0);

    $proposal = Proposal::create(['job_id' => $job->id, 'freelancer_id' => $freelancer->id, 'cover_letter' => 'A', 'proposed_budget_points' => 1000, 'points_spent' => 2, 'status' => 'accepted']);
    
    $contract = Contract::create([
        'job_id' => $job->id,
        'proposal_id' => $proposal->id,
        'client_id' => $client->id,
        'freelancer_id' => $freelancer->id,
        'contract_points' => 1000,
        'status' => 'completed', // Already completed
        'started_at' => now(),
    ]);

    $action = app(CompleteContractAction::class);
    $action->execute($contract, $client);
})->throws(\Exception::class, 'Only active contracts can be completed.');
