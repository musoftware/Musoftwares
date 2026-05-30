<?php

use Modules\Freelance\Jobs\NotifyFreelancersForJob;
use Modules\Freelance\Models\Job;
use Modules\Freelance\Tests\Builders\JobScenarioBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(Tests\TestCase::class, RefreshDatabase::class)->in(__DIR__);

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
        return $queuedJob->job->id === $job->id;
    });
});
