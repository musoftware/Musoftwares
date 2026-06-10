<?php

uses(\Tests\TestCase::class, \Illuminate\Foundation\Testing\RefreshDatabase::class);

use Modules\Freelance\Models\Job;
use Modules\Freelance\Models\Proposal;
use App\Models\User;

use Illuminate\Foundation\Testing\RefreshDatabase;



it('allows a freelancer to submit a proposal via web routes', function () {
    $this->withoutMiddleware([\App\Http\Middleware\EnsureOnboardingCompleted::class, \App\Http\Middleware\SubscriptionMiddleware::class]);
    
    $client = User::factory()->create();
    $freelancer = User::factory()->create(['points_balance' => 50]);
    

    $job = Job::create([
        'client_id' => $client->id,
        'title' => 'Web Job',
        'description' => 'Web desc',
        'budget_points' => 1000,
        'min_proposal_points' => 0,
        'type' => 'fixed',
        'duration' => '1_month',
        'status' => 'open'
    ]);

    $payload = [
        'cover_letter' => 'My proposal',
        'bid_amount' => 800,
        'points_spent' => 2
    ];

    $response = $this->actingAs($freelancer)->post("/freelance/jobs/{$job->id}/proposals", $payload);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect();

    $this->assertDatabaseHas('freelance_proposals', [
        'job_id' => $job->id,
        'freelancer_id' => $freelancer->id,
        'status' => 'pending'
    ]);

    expect($freelancer->fresh()->points_balance)->toBe(48); // Assumes cost is 2
});
