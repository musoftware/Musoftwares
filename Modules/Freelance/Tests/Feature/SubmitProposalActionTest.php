<?php

uses(\Tests\TestCase::class, \Illuminate\Foundation\Testing\DatabaseTransactions::class);

use Modules\Freelance\Domains\Proposal\Actions\SubmitProposalAction;
use Modules\Freelance\Domains\Proposal\DTOs\SubmitProposalData;
use App\Models\User;
use Modules\Freelance\Models\Job;
use App\Models\PointTransaction;

it('submits a proposal successfully and deducts points', function () {
    $client = User::factory()->create();
    $freelancer = User::factory()->create([
        'points_balance' => 10,
    ]);

    $job = Job::create([
        'client_id' => $client->id,
        'title' => 'Test Job',
        'description' => 'Test description',
        'budget_points' => 500,
        'min_proposal_points' => 0,
        'type' => 'fixed',
        'status' => 'open',
    ]);

    $data = new SubmitProposalData(
        jobId: $job->id,
        freelancerId: $freelancer->id,
        coverLetter: 'I can do this!',
        bidAmount: 400.0,
        currencyId: 1,
        pointsSpent: 2,
    );

    $action = app(SubmitProposalAction::class);
    $proposal = $action->execute($data, $job, $freelancer);

    expect($proposal->freelancer_id)->toBe($freelancer->id);
    expect($proposal->status)->toBe('pending');

    // Check points deduction
    expect($freelancer->fresh()->points_balance)->toBe(8);

    // Check point transaction
    $this->assertDatabaseHas('point_transactions', [
        'user_id' => $freelancer->id,
        'points' => 2,
        'type' => 'spent',
    ]);
});

it('fails to submit proposal if already submitted', function () {
    $client = User::factory()->create();
    $freelancer = User::factory()->create([
        'points_balance' => 10,
    ]);

    $job = Job::create([
        'client_id' => $client->id,
        'title' => 'Test Job',
        'description' => 'Test description',
        'budget_points' => 500,
        'min_proposal_points' => 0,
        'type' => 'fixed',
        'status' => 'open',
    ]);

    $job->proposals()->create([
        'freelancer_id' => $freelancer->id,
        'cover_letter' => 'First bid',
        'proposed_budget_points' => 500,
        'points_spent' => 2,
        'status' => 'pending',
    ]);

    $data = new SubmitProposalData(
        jobId: $job->id,
        freelancerId: $freelancer->id,
        coverLetter: 'Second bid',
        bidAmount: 400.0,
        currencyId: 1,
        pointsSpent: 2,
    );

    $action = app(SubmitProposalAction::class);
    
    expect(fn () => $action->execute($data, $job, $freelancer))->toThrow(\Exception::class, 'You have already submitted a proposal for this job.');
});
