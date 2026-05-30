<?php

use Modules\Freelance\Domains\Contract\Actions\AcceptProposalAction;
use Modules\Freelance\Tests\Builders\JobScenarioBuilder;
use Modules\Freelance\Models\Proposal;
use Modules\Freelance\Models\Contract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(Tests\TestCase::class, RefreshDatabase::class)->in(__DIR__);

beforeEach(function () {
    Event::fake();
});

it('prevents accepting multiple proposals for the same job simultaneously', function () {
    $scenario = JobScenarioBuilder::create()
        ->withClient(points: 100, balance: 10000)
        ->withFreelancers(2, points: 50)
        ->withJob(['status' => 'open']);

    $client = $scenario->getClient();
    $job = $scenario->getJob();
    $freelancers = $scenario->getFreelancers();

    $proposal1 = Proposal::create(['job_id' => $job->id, 'freelancer_id' => $freelancers[0]->id, 'cover_letter' => 'A', 'proposed_budget_points' => 1000, 'points_spent' => 2, 'status' => 'pending']);
    $proposal2 = Proposal::create(['job_id' => $job->id, 'freelancer_id' => $freelancers[1]->id, 'cover_letter' => 'B', 'proposed_budget_points' => 1000, 'points_spent' => 2, 'status' => 'pending']);

    $action = app(AcceptProposalAction::class);
    
    // Accept proposal 1
    $contract1 = $action->execute($proposal1, $client);

    expect($contract1)->toBeInstanceOf(Contract::class);

    // Attempt to accept proposal 2. The proposal status was changed to 'rejected' during the first acceptance.
    // If we assume a stale read (where proposal2 is somehow still in memory with 'pending'),
    // we should have a validation rule preventing creating a contract for a job that is already 'in_progress'.

    // Wait, the AcceptProposalAction currently doesn't check if the Job is already 'in_progress'.
    // Let's assert that we throw an exception or that the proposal status is properly validated.
    // Let's see if we reload proposal2:
    $proposal2->refresh();
    
    // In our test, proposal2 is now 'rejected'
    expect($proposal2->status)->toBe('rejected');

    // If we pass the rejected proposal to AcceptProposalAction, what happens? 
    // Currently, AcceptProposalAction doesn't check if the proposal is 'pending'. It just accepts it.
    // We should test that this is handled. Since we are writing tests, we will write the EXPECTED behavior.
    
    // EXPECTED: Accepting a non-pending proposal or a job that is not 'open' should fail.
    try {
        $action->execute($proposal2, $client);
        // NOTE: If this fails, the AcceptProposalAction needs an update to check job/proposal status.
        $this->fail('Should not be able to accept a rejected proposal or for an already in-progress job.');
    } catch (\Exception $e) {
        // Assert some exception is thrown. If the action is missing this check, this test will fail
        // and highlight the bug to the developer.
        expect(true)->toBeTrue(); 
    }

    // Only 1 contract should exist
    expect(Contract::count())->toBe(1);
});
