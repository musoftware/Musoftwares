<?php

use Modules\Freelance\Domains\Job\Actions\PostJobAction;
use Modules\Freelance\Domains\Job\DTOs\PostJobData;
use App\Models\User;
use App\Models\PointTransaction;
use Modules\Freelance\Models\Job;
use Illuminate\Support\Facades\Queue;
use Modules\Freelance\Jobs\NotifyFreelancersForJob;

it('posts a job successfully and deducts points', function () {
    Queue::fake();

    $user = User::factory()->create([
        'points_balance' => 20,
    ]);

    $data = new PostJobData(
        clientId: $user->id,
        title: 'Test Job',
        description: 'Test description',
        budget: 500,
        currencyId: 1,
        type: 'fixed',
        duration: '1 week',
        skills: []
    );

    $action = app(PostJobAction::class);
    $job = $action->execute($data, $user);

    expect($job)->toBeInstanceOf(Job::class);
    expect($job->title)->toBe('Test Job');
    expect($job->status)->toBe('open');

    // Check points deduction
    expect($user->fresh()->points_balance)->toBe(10);

    // Check point transaction
    $this->assertDatabaseHas('point_transactions', [
        'user_id' => $user->id,
        'points' => 10,
        'type' => 'spent',
    ]);

    // Check if notification was dispatched
    Queue::assertPushed(NotifyFreelancersForJob::class);
});

it('fails to post a job if insufficient points', function () {
    $user = User::factory()->create([
        'points_balance' => 5,
    ]);

    $data = new PostJobData(
        clientId: $user->id,
        title: 'Test Job',
        description: 'Test description',
        budget: 500,
        currencyId: 1,
        type: 'fixed',
        duration: '1 week',
        skills: []
    );

    $action = app(PostJobAction::class);
    
    expect(fn () => $action->execute($data, $user))->toThrow(\Exception::class, 'Insufficient points to post a job.');
});
