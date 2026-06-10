<?php

uses(\Tests\TestCase::class, \Illuminate\Foundation\Testing\RefreshDatabase::class);

use Modules\Freelance\Models\Job;
use Modules\Freelance\Models\Proposal;
use App\Models\User;

it('prevents client from closing another clients job', function () {
    $this->withoutMiddleware([\App\Http\Middleware\EnsureOnboardingCompleted::class, \App\Http\Middleware\SubscriptionMiddleware::class]);
    
    $client1 = User::factory()->create();
    $client2 = User::factory()->create();
    
    $job = Job::factory()->create(['client_id' => $client1->id, 'status' => 'open']);

    // Attempting to delete the job as another client
    $response = $this->actingAs($client2)->delete("/freelance/jobs/{$job->id}");

    $response->assertStatus(403); // Gate::authorize('delete', $job) should throw 403
});

it('prevents mass assignment of points_spent in proposals to bypass minimums', function () {
    $this->withoutMiddleware([\App\Http\Middleware\EnsureOnboardingCompleted::class, \App\Http\Middleware\SubscriptionMiddleware::class]);
    
    $client = User::factory()->create();
    $freelancer = User::factory()->create(['points_balance' => 100]);
    
    $job = Job::factory()->create(['client_id' => $client->id, 'min_proposal_points' => 50, 'status' => 'open']);

    $payload = [
        'cover_letter' => 'Test',
        'bid_amount' => 100,
        'points_spent' => 10 // Below min 50
    ];

    $response = $this->actingAs($freelancer)->post("/freelance/jobs/{$job->id}/proposals", $payload);

    // Should return back with errors because the action will throw an exception
    $response->assertSessionHasErrors();
});
