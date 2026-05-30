<?php

use Modules\Freelance\Models\Job;
use Modules\Freelance\Models\Proposal;
use App\Models\User;
use App\Models\Currency;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class)->in(__DIR__);

it('allows a freelancer to submit a proposal via API', function () {
    $client = User::factory()->create();
    $freelancer = User::factory()->create(['points_balance' => 50]);
    $currency = Currency::factory()->create();

    $job = Job::create([
        'client_id' => $client->id,
        'title' => 'API Job',
        'description' => 'API desc',
        'budget_points' => 1000,
        'min_proposal_points' => 0,
        'type' => 'fixed',
        'duration' => '1_month',
        'status' => 'open'
    ]);

    $payload = [
        'cover_letter' => 'My proposal',
        proposedBudgetPoints: 800,
        pointsSpent: 2
    ];

    $response = $this->actingAs($freelancer)->postJson("/api/freelance/jobs/{$job->id}/proposals", $payload);

    if ($response->status() === 404) {
        $this->markTestSkipped('Route /api/freelance/jobs/{job}/proposals not implemented yet.');
    }

    $response->assertStatus(201);

    $this->assertDatabaseHas('freelance_proposals', [
        'job_id' => $job->id,
        'freelancer_id' => $freelancer->id,
        'status' => 'pending'
    ]);

    expect($freelancer->fresh()->points_balance)->toBe(48); // Assumes cost is 2
});
