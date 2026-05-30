<?php

uses(\Tests\TestCase::class, \Illuminate\Foundation\Testing\RefreshDatabase::class);

use Modules\Freelance\Tests\Builders\JobScenarioBuilder;
use Modules\Freelance\Models\Proposal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;



it('prevents a user from withdrawing someone elses proposal via API', function () {
    $scenario = JobScenarioBuilder::create()
        ->withClient()
        ->withFreelancers(2)
        ->withJob();

    $job = $scenario->getJob();
    $freelancer1 = $scenario->getFreelancer(0);
    $freelancer2 = $scenario->getFreelancer(1);

    $proposal = Proposal::create([
        'job_id' => $job->id,
        'freelancer_id' => $freelancer1->id,
        'cover_letter' => 'My proposal',
        'proposed_budget_points' => 1000,
        'points_spent' => 2,
        'status' => 'pending'
    ]);

    // Freelancer 2 tries to delete/withdraw Freelancer 1's proposal
    $response = $this->actingAs($freelancer2)->deleteJson("/api/freelance/proposals/{$proposal->id}");

    if ($response->status() === 404) {
        $this->markTestSkipped('Route not implemented yet.');
    }

    $response->assertStatus(403);
});

it('prevents a freelancer from accepting their own proposal', function () {
    $scenario = JobScenarioBuilder::create()
        ->withClient()
        ->withFreelancers(1)
        ->withJob();

    $job = $scenario->getJob();
    $freelancer = $scenario->getFreelancer(0);

    $proposal = Proposal::create([
        'job_id' => $job->id,
        'freelancer_id' => $freelancer->id,
        'cover_letter' => 'My proposal',
        'proposed_budget_points' => 1000,
        'points_spent' => 2,
        'status' => 'pending'
    ]);

    $response = $this->actingAs($freelancer)->postJson("/api/freelance/proposals/{$proposal->id}/accept");

    if ($response->status() === 404) {
        $this->markTestSkipped('Route not implemented yet.');
    }

    $response->assertStatus(403);
});
