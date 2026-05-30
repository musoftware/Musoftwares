<?php

uses(\Tests\TestCase::class, \Illuminate\Foundation\Testing\RefreshDatabase::class);

use Modules\Freelance\Domains\Proposal\Actions\SubmitProposalAction;
use Modules\Freelance\Domains\Proposal\DTOs\SubmitProposalData;
use Modules\Freelance\Tests\Builders\JobScenarioBuilder;
use Modules\Freelance\Models\Proposal;
use Illuminate\Foundation\Testing\RefreshDatabase;

use Modules\Freelance\Domains\Finance\Actions\DeductPointsAction;



it('submits a proposal successfully and deducts points', function () {
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

    $proposal = $action->execute($data, $job, $freelancer);

    expect($proposal)->toBeInstanceOf(Proposal::class)
        ->and($proposal->cover_letter)->toBe('I am the best fit for this.')
        ->and($proposal->status)->toBe('pending')
        ->and($proposal->freelancer_id)->toBe($freelancer->id);

    $freelancer->refresh();
    expect($freelancer->points_balance)->toBe(48); // 50 - 2
});

it('prevents submitting multiple proposals for the same job', function () {
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

    // Second submission should fail
    $action->execute($data, $job, $freelancer);
})->throws(\Exception::class, 'You have already submitted a proposal for this job.');

it('throws exception if freelancer has insufficient points', function () {
    $scenario = JobScenarioBuilder::create()
        ->withClient()
        ->withFreelancers(1, points: 1) // Cost is 2
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

    $action->execute($data, $job, $freelancer);
})->throws(\Exception::class, 'Insufficient points to submit a proposal.');

it('rolls back proposal creation if point deduction fails', function () {
    $scenario = JobScenarioBuilder::create()
        ->withClient()
        ->withFreelancers(1, points: 50)
        ->withJob();

    $freelancer = $scenario->getFreelancer(0);
    $job = $scenario->getJob();
    

    $deductActionMock = Mockery::mock(DeductPointsAction::class);
    $deductActionMock->shouldReceive('execute')->andThrow(new \Exception('Point deduction failed'));
    $this->app->instance(DeductPointsAction::class, $deductActionMock);

    $action = app(SubmitProposalAction::class);
    $data = new SubmitProposalData(
        jobId: $job->id,
        freelancerId: $freelancer->id,
        coverLetter: 'Will fail.',
        proposedBudgetPoints: 1000,
        pointsSpent: 2
    );

    try {
        $action->execute($data, $job, $freelancer);
    } catch (\Exception $e) {
        expect($e->getMessage())->toBe('Point deduction failed');
    }

    expect(Proposal::count())->toBe(0);
});
