<?php

uses(\Tests\TestCase::class, \Illuminate\Foundation\Testing\DatabaseTransactions::class);

use Modules\Freelance\Domains\Contract\Actions\AcceptProposalAction;
use Modules\Freelance\Tests\Builders\JobScenarioBuilder;
use Modules\Freelance\Models\Proposal;
use Modules\Freelance\Models\Contract;
use Illuminate\Foundation\Testing\DatabaseTransactions;

use Illuminate\Support\Facades\Event;
use App\Events\ProposalAccepted;



beforeEach(function () {
    Event::fake([ProposalAccepted::class]);
});

it('accepts a proposal, refunds other freelancers, and creates a contract', function () {
    $scenario = JobScenarioBuilder::create()
        ->withClient(points: 100, balance: 5000)
        ->withFreelancers(3, points: 50)
        ->withJob(['status' => 'open']);

    $client = $scenario->getClient();
    $job = $scenario->getJob();
    $currencyId = $job->currency_id;
    $freelancers = $scenario->getFreelancers();

    // Create 3 proposals
    $proposal1 = Proposal::create(['job_id' => $job->id, 'freelancer_id' => $freelancers[0]->id, 'cover_letter' => 'A', 'proposed_budget_points' => 1000, 'points_spent' => 2, 'status' => 'pending']);
    $proposal2 = Proposal::create(['job_id' => $job->id, 'freelancer_id' => $freelancers[1]->id, 'cover_letter' => 'B', 'proposed_budget_points' => 1500, 'points_spent' => 3, 'status' => 'pending']);
    $proposal3 = Proposal::create(['job_id' => $job->id, 'freelancer_id' => $freelancers[2]->id, 'cover_letter' => 'C', 'proposed_budget_points' => 2000, 'points_spent' => 4, 'status' => 'pending']);

    $action = app(AcceptProposalAction::class);
    
    // Accept proposal 1
    $contract = $action->execute($proposal1, $client);

    expect($contract)->toBeInstanceOf(Contract::class)
        ->and($contract->contract_points)->toBe(1000)
        ->and($contract->status)->toBe('active')
        ->and($contract->freelancer_id)->toBe($freelancers[0]->id);

    // Job should be in progress
    expect($job->fresh()->status->getValue())->toBe('in_progress');

    // Proposal 1 is accepted, others rejected
    expect($proposal1->fresh()->status)->toBe('accepted')
        ->and($proposal2->fresh()->status)->toBe('rejected')
        ->and($proposal3->fresh()->status)->toBe('rejected');

    // Other freelancers refunded points based on their points_spent
    expect($freelancers[1]->fresh()->points_balance)->toBe(53) // 50 + 3
        ->and($freelancers[2]->fresh()->points_balance)->toBe(54); // 50 + 4

    // Winning freelancer points are unchanged
    expect($freelancers[0]->fresh()->points_balance)->toBe(50);

    Event::assertDispatched(ProposalAccepted::class);
});

it('throws exception if non-client tries to accept proposal', function () {
    $scenario = JobScenarioBuilder::create()
        ->withClient(points: 100, balance: 5000)
        ->withFreelancers(2, points: 50)
        ->withJob();

    $job = $scenario->getJob();
    $freelancer = $scenario->getFreelancer(0);
    $otherUser = $scenario->getFreelancer(1); // acting as random user

    $proposal = Proposal::create(['job_id' => $job->id, 'freelancer_id' => $freelancer->id, 'cover_letter' => 'A', 'proposed_budget_points' => 1000, 'points_spent' => 2, 'status' => 'pending']);

    $action = app(AcceptProposalAction::class);
    
    $action->execute($proposal, $otherUser);
})->throws(\Exception::class, 'Unauthorized to accept proposals for this job.');
