<?php

uses(\Tests\TestCase::class, \Illuminate\Foundation\Testing\RefreshDatabase::class);

use Modules\Freelance\Domains\Job\Actions\PostJobAction;
use Modules\Freelance\Domains\Job\DTOs\PostJobData;
use Modules\Freelance\Domains\Finance\Actions\DeductPointsAction;
use Modules\Freelance\Jobs\NotifyFreelancersForJob;
use App\Models\User;

use Modules\Freelance\Models\Job;
use Modules\Freelance\Models\Skill;
use Illuminate\Support\Facades\Queue;
use Illuminate\Foundation\Testing\RefreshDatabase;



beforeEach(function () {
    Queue::fake();
});

it('posts a job successfully and deducts points', function () {
    $client = User::factory()->create(['points_balance' => 50]);
    
    $skill = Skill::create(['name' => 'PHP', 'description' => 'PHP']);

    $action = app(PostJobAction::class);
    $data = new PostJobData(
        clientId: $client->id,
        title: 'Enterprise Architecture',
        description: 'Build an enterprise system',
        budgetPoints: 5000,
        minProposalPoints: 0,
        type: 'fixed',
        duration: '3_months',
        skills: [$skill->id]
    );

    $job = $action->execute($data, $client);

    expect($job)->toBeInstanceOf(Job::class)
        ->and($job->title)->toBe('Enterprise Architecture')
        ->and($job->status->getValue())->toBe('open')
        ->and($job->skills)->toHaveCount(1);

    // Refresh client to check points
    $client->refresh();
    expect($client->points_balance)->toBe(25); // 50 - 25

    // Assert Notification Job was dispatched
    Queue::assertPushed(NotifyFreelancersForJob::class, function ($queuedJob) use ($job) {
        return $queuedJob->freelanceJob->id === $job->id;
    });
});

it('throws exception if client has insufficient points', function () {
    $client = User::factory()->create(['points_balance' => 5]); // Less than postCost (10)
    

    $action = app(PostJobAction::class);
    $data = new PostJobData(
        clientId: $client->id,
        title: 'Enterprise Architecture',
        description: 'Build an enterprise system',
        budgetPoints: 5000,
        minProposalPoints: 0,
        type: 'fixed',
        duration: '3_months',
        skills: []
    );

    // Should throw exception
    $action->execute($data, $client);
})->throws(\Exception::class, 'Insufficient points to post a job.');

it('reverts database transaction if point deduction fails', function () {
    // This requires mocking the DeductPointsAction to throw an error 
    // and ensuring no job was created.
    $client = User::factory()->create(['points_balance' => 50]);
    

    // Mocking to simulate an inner failure
    $deductActionMock = Mockery::mock(DeductPointsAction::class);
    $deductActionMock->shouldReceive('execute')->andThrow(new \Exception('Database locking failed'));
    $this->app->instance(DeductPointsAction::class, $deductActionMock);

    $action = app(PostJobAction::class);
    $data = new PostJobData(
        clientId: $client->id,
        title: 'Will Fail',
        description: 'Should not exist',
        budgetPoints: 100,
        minProposalPoints: 0,
        type: 'fixed',
        duration: '1_week',
        skills: []
    );

    try {
        $action->execute($data, $client);
    } catch (\Exception $e) {
        expect($e->getMessage())->toBe('Database locking failed');
    }

    // Assert job was NOT created due to transaction rollback
    expect(Job::count())->toBe(0);
});
