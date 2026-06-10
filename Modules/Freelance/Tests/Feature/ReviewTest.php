<?php

uses(\Tests\TestCase::class, \Illuminate\Foundation\Testing\RefreshDatabase::class);

use App\Models\User;
use Modules\Freelance\Models\Job;
use Modules\Freelance\Models\Contract;
use Modules\Freelance\Models\Review;

it('allows a user to submit a review for a completed contract and keeps it hidden until the other party reviews', function () {
    $this->withoutMiddleware([\App\Http\Middleware\EnsureOnboardingCompleted::class, \App\Http\Middleware\SubscriptionMiddleware::class]);

    $client = User::factory()->create();
    $freelancer = User::factory()->create();

    $job = Job::factory()->create(['client_id' => $client->id]);
    $contract = Contract::factory()->create([
        'job_id' => $job->id,
        'client_id' => $client->id,
        'freelancer_id' => $freelancer->id,
        'status' => 'completed',
    ]);

    // Client reviews Freelancer
    $response = $this->actingAs($client)->post("/freelance/contracts/{$contract->id}/reviews", [
        'rating' => 5,
        'comment' => 'Great work!',
    ]);

    $response->assertSessionHas('success', __('freelance.review_submitted_hidden'));

    $this->assertDatabaseHas('freelance_reviews', [
        'contract_id' => $contract->id,
        'reviewer_id' => $client->id,
        'reviewee_id' => $freelancer->id,
        'rating' => 5,
        'is_visible' => 0,
    ]);

    // Freelancer reviews Client
    $response2 = $this->actingAs($freelancer)->post("/freelance/contracts/{$contract->id}/reviews", [
        'rating' => 4,
        'comment' => 'Good client.',
    ]);

    $response2->assertSessionHas('success', __('freelance.review_submitted_visible'));

    $this->assertDatabaseHas('freelance_reviews', [
        'contract_id' => $contract->id,
        'reviewer_id' => $freelancer->id,
        'reviewee_id' => $client->id,
        'rating' => 4,
        'is_visible' => 1,
    ]);

    // Check that client's review is also visible now
    $this->assertDatabaseHas('freelance_reviews', [
        'contract_id' => $contract->id,
        'reviewer_id' => $client->id,
        'is_visible' => 1,
    ]);
});

it('prevents reviewing a non-completed contract', function () {
    $this->withoutMiddleware([\App\Http\Middleware\EnsureOnboardingCompleted::class, \App\Http\Middleware\SubscriptionMiddleware::class]);

    $client = User::factory()->create();
    $freelancer = User::factory()->create();

    $job = Job::factory()->create(['client_id' => $client->id]);
    $contract = Contract::factory()->create([
        'job_id' => $job->id,
        'client_id' => $client->id,
        'freelancer_id' => $freelancer->id,
        'status' => 'active', // NOT completed
    ]);

    $response = $this->actingAs($client)->post("/freelance/contracts/{$contract->id}/reviews", [
        'rating' => 5,
    ]);

    $response->assertSessionHas('error', 'You can only review completed contracts.');
});

it('prevents non-participants from reviewing a contract', function () {
    $this->withoutMiddleware([\App\Http\Middleware\EnsureOnboardingCompleted::class, \App\Http\Middleware\SubscriptionMiddleware::class]);

    $client = User::factory()->create();
    $freelancer = User::factory()->create();
    $stranger = User::factory()->create();

    $job = Job::factory()->create(['client_id' => $client->id]);
    $contract = Contract::factory()->create([
        'job_id' => $job->id,
        'client_id' => $client->id,
        'freelancer_id' => $freelancer->id,
        'status' => 'completed',
    ]);

    $response = $this->actingAs($stranger)->post("/freelance/contracts/{$contract->id}/reviews", [
        'rating' => 5,
    ]);

    $response->assertStatus(403);
});
