<?php

uses(\Tests\TestCase::class, \Illuminate\Foundation\Testing\DatabaseTransactions::class);

use Modules\Freelance\Domains\Proposal\Actions\SubmitProposalAction;
use Modules\Freelance\Domains\Proposal\DTOs\SubmitProposalData;
use Modules\Freelance\Tests\Builders\JobScenarioBuilder;
use Modules\Freelance\Models\Proposal;

use Illuminate\Foundation\Testing\DatabaseTransactions;



it('prevents a freelancer from submitting duplicate proposals for the same job', function () {
    $scenario = JobScenarioBuilder::create()
        ->withClient()
        ->withFreelancers(1, points: 50)
        ->withJob();

    $freelancer = $scenario->getFreelancer(0);
    $job = $scenario->getJob();


    $action = app(SubmitProposalAction::class);
    $data = new SubmitProposalData(
        jobId: $job->id,
        freelancerId: $freelancer->id,
        coverLetter: 'I am the best fit for this.',
        proposedBudgetPoints: 1000,
        pointsSpent: 2
    );

    // First submission
    $action->execute($data, $job, $freelancer);

    // Assert proposal created
    expect(Proposal::where('job_id', $job->id)->where('freelancer_id', $freelancer->id)->count())->toBe(1);

    // Second submission - should fail
    try {
        $action->execute($data, $job, $freelancer);
        $this->fail('Expected an exception for duplicate proposal submission.');
    } catch (\Exception $e) {
        expect($e->getMessage())->toBe('You have already submitted a proposal for this job.');
    }

    // Verify it wasn't inserted
    expect(Proposal::where('job_id', $job->id)->where('freelancer_id', $freelancer->id)->count())->toBe(1);
    
    // Points should only be deducted once
    expect($freelancer->fresh()->points_balance)->toBe(48);
});

// For actual multi-process concurrency testing (e.g. 2 threads hitting the action exactly at the same ms), 
// it relies on Database Unique Constraints on (job_id, freelancer_id). 
// This test ensures the application layer guard is functional.
