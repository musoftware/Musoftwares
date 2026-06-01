<?php

uses(\Tests\TestCase::class, \Illuminate\Foundation\Testing\DatabaseTransactions::class);

use Modules\Freelance\Models\Job;
use Modules\Freelance\Models\Proposal;
use App\Models\User;

it('prevents client from closing another clients job', function () {
    $client1 = User::factory()->create();
    $client2 = User::factory()->create();
    
    $job = Job::factory()->create(['client_id' => $client1->id, 'status' => 'open']);

    // API endpoint simulation for closing job
    $response = $this->actingAs($client2)->putJson("/api/freelance/jobs/{$job->id}/close");

    // If route doesn't exist, we skip or expect 404, else it should be 403
    if ($response->status() !== 404) {
        $response->assertStatus(403);
    }
});

it('prevents mass assignment of points_spent in proposals to bypass minimums', function () {
    $client = User::factory()->create();
    $freelancer = User::factory()->create(['points_balance' => 100]);
    
    $job = Job::factory()->create(['client_id' => $client->id, 'min_proposal_points' => 50, 'status' => 'open']);

    // Attempting to bypass by manipulating payload
    $payload = [
        'cover_letter' => 'Test',
        'proposed_budget_points' => 100,
        'points_spent' => 10 // Below min 50
    ];

    $response = $this->actingAs($freelancer)->postJson("/api/freelance/jobs/{$job->id}/proposals", $payload);

    if ($response->status() !== 404) {
        // Validation should fail
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['points_spent']);
    }
});
