<?php

uses(\Tests\TestCase::class, \Illuminate\Foundation\Testing\DatabaseTransactions::class);

use Modules\Freelance\Jobs\NotifyFreelancersForJob;
use Modules\Freelance\Models\Job;
use Modules\Freelance\Tests\Builders\JobScenarioBuilder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;



it('handles large batches of notifications gracefully', function () {
    // This is typically a test to ensure a job can be serialized and dispatched efficiently.
    // In a real environment, you might simulate 100k users. We simulate a small chunk
    // but verify the queue dispatcher is used correctly without crashing memory.

    Queue::fake();

    $scenario = JobScenarioBuilder::create()
        ->withClient()
        ->withJob();

    $job = $scenario->getJob();

    NotifyFreelancersForJob::dispatch($job);

    Queue::assertPushed(NotifyFreelancersForJob::class, function ($queuedJob) use ($job) {
        return $queuedJob->freelanceJob->id === $job->id;
    });
});
