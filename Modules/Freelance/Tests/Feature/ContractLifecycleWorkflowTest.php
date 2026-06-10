<?php

uses(\Tests\TestCase::class, \Illuminate\Foundation\Testing\RefreshDatabase::class);

use Modules\Freelance\Models\Job;
use Modules\Freelance\Models\Proposal;
use Modules\Freelance\Models\Contract;
use App\Models\User;

use Illuminate\Foundation\Testing\RefreshDatabase;



it('handles full contract lifecycle from acceptance to completion via web routes', function () {
    $this->withoutMiddleware([\App\Http\Middleware\EnsureOnboardingCompleted::class, \App\Http\Middleware\SubscriptionMiddleware::class]);

    $client = User::factory()->create();
    $freelancer = User::factory()->create(['points_balance' => 50]);
    
    $job = Job::create([
        'client_id' => $client->id,
        'title' => 'Web Lifecycle Job',
        'description' => 'Desc',
        'budget_points' => 1000,
        'min_proposal_points' => 0,
        'type' => 'fixed',
        'duration' => '1_month',
        'status' => 'open'
    ]);

    $proposal = Proposal::create([
        'job_id' => $job->id,
        'freelancer_id' => $freelancer->id,
        'cover_letter' => 'My proposal',
        'proposed_budget_points' => 1000,
        'points_spent' => 2,
        'status' => 'pending'
    ]);

    // 1. Accept Proposal
    $acceptResponse = $this->actingAs($client)->post("/freelance/proposals/{$proposal->id}/accept");

    $acceptResponse->assertSessionHasNoErrors();
    $acceptResponse->assertRedirect(); // Expecting a redirect back

    $this->assertDatabaseHas('freelance_contracts', [
        'proposal_id' => $proposal->id,
        'status' => 'active'
    ]);

    $contract = Contract::where('proposal_id', $proposal->id)->first();

    // 2. Complete Contract
    $completeResponse = $this->actingAs($client)->post("/freelance/contracts/{$contract->id}/complete");
    $completeResponse->assertSessionHasNoErrors();
    $completeResponse->assertRedirect();

    $this->assertDatabaseHas('freelance_contracts', [
        'id' => $contract->id,
        'status' => 'completed'
    ]);

    $this->assertDatabaseHas('freelance_jobs', [
        'id' => $job->id,
        'status' => 'completed'
    ]);
});
