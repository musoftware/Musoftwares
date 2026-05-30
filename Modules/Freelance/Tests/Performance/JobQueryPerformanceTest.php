<?php

use Modules\Freelance\Models\Job;
use Modules\Freelance\Models\Proposal;
use Modules\Freelance\Tests\Builders\JobScenarioBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(Tests\TestCase::class, RefreshDatabase::class)->in(__DIR__);

it('loads jobs and their related counts without N+1 queries', function () {
    // Generate 5 jobs with 3 proposals each
    $scenario = JobScenarioBuilder::create()
        ->withClient()
        ->withFreelancers(3);
    
    $client = $scenario->getClient();
    $freelancers = $scenario->getFreelancers();
    
    for ($i = 0; $i < 5; $i++) {
        $job = Job::create([
            'client_id' => $client->id,
            'title' => "Job {$i}",
            'description' => 'Desc',
            'budget' => 1000,
            'currency_id' => 1,
            'type' => 'fixed',
            'duration' => '1_month',
            'status' => 'open'
        ]);

        foreach ($freelancers as $freelancer) {
            Proposal::create([
                'job_id' => $job->id,
                'freelancer_id' => $freelancer->id,
                'cover_letter' => 'Test',
                'bid_amount' => 500,
                'currency_id' => 1,
                'status' => 'pending'
            ]);
        }
    }

    DB::enableQueryLog();

    // In a real controller, you would call your endpoint, but here we test the Eloquent query directly.
    $jobs = Job::withCount('proposals')->with('client')->get();

    // Verify relations
    expect($jobs->first()->proposals_count)->toBe(3)
        ->and($jobs->first()->client->id)->toBe($client->id);

    $queries = DB::getQueryLog();
    
    // 1 query for Jobs (with proposals_count)
    // 1 query for Clients
    expect(count($queries))->toBeLessThanOrEqual(2);

    DB::disableQueryLog();
});
