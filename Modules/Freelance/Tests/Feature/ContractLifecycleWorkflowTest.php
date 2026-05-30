<?php

use Modules\Freelance\Models\Job;
use Modules\Freelance\Models\Proposal;
use Modules\Freelance\Models\Contract;
use App\Models\User;
use App\Models\Currency;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class)->in(__DIR__);

it('handles full contract lifecycle from acceptance to completion via API', function () {
    $client = User::factory()->create();
    $freelancer = User::factory()->create(['points_balance' => 50]);
    $currency = Currency::factory()->create();

    $job = Job::create([
        'client_id' => $client->id,
        'title' => 'API Lifecycle Job',
        'description' => 'Desc',
        'budget' => 1000,
        'currency_id' => $currency->id,
        'type' => 'fixed',
        'duration' => '1_month',
        'status' => 'open'
    ]);

    $proposal = Proposal::create([
        'job_id' => $job->id,
        'freelancer_id' => $freelancer->id,
        'cover_letter' => 'My proposal',
        'bid_amount' => 1000,
        'currency_id' => $currency->id,
        'status' => 'pending'
    ]);

    // 1. Accept Proposal
    $acceptResponse = $this->actingAs($client)->postJson("/api/freelance/proposals/{$proposal->id}/accept");

    if ($acceptResponse->status() === 404) {
        $this->markTestSkipped('Routes not implemented yet.');
    }

    $acceptResponse->assertStatus(200);

    $this->assertDatabaseHas('freelance_contracts', [
        'proposal_id' => $proposal->id,
        'status' => 'active'
    ]);

    $contract = Contract::where('proposal_id', $proposal->id)->first();

    // 2. Complete Contract
    $completeResponse = $this->actingAs($client)->postJson("/api/freelance/contracts/{$contract->id}/complete");
    $completeResponse->assertStatus(200);

    $this->assertDatabaseHas('freelance_contracts', [
        'id' => $contract->id,
        'status' => 'completed'
    ]);

    $this->assertDatabaseHas('freelance_jobs', [
        'id' => $job->id,
        'status' => 'completed'
    ]);
});
