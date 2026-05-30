<?php

uses(\Tests\TestCase::class, \Illuminate\Foundation\Testing\RefreshDatabase::class);

use Modules\Freelance\Models\Job;
use Modules\Freelance\Models\Skill;
use App\Models\User;
use App\Models\Currency;
use App\Models\CurrenciesExchange;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();

    // Create a default USD currency if it doesn't exist
    $this->usdCurrency = Currency::firstOrCreate(
        ['currency' => 'USD'],
        ['symbol' => '$', 'string_format' => '$ %s', 'exchange_rate' => 1]
    );

    $this->egpCurrency = Currency::firstOrCreate(
        ['currency' => 'EGP'],
        ['symbol' => 'EGP', 'string_format' => '%s EGP', 'exchange_rate' => 50]
    );
});

it('allows a user to post a job with existing approved skills', function () {
    $client = User::factory()->create([
        'points_balance' => 50, 
        'currency_id' => $this->usdCurrency->id,
        'onboarding_completed' => true
    ]);
    $skill = Skill::create(['name' => 'Laravel', 'status' => 'approved', 'created_by' => null]);
    
    $payload = [
        'title' => 'Feature Test Job',
        'description' => 'Full workflow test',
        'budget' => 500,
        'currency_id' => $this->usdCurrency->id,
        'min_proposal_points' => 0,
        'type' => 'fixed',
        'duration' => '1_month',
        'skills' => [$skill->id]
    ];

    $response = $this->actingAs($client)->post('/freelance/jobs', $payload);

    $response->assertRedirect(route('freelance.my-jobs'));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('freelance_jobs', [
        'title' => 'Feature Test Job',
        'client_id' => $client->id,
        'status' => 'open',
        'budget' => 500,
        'currency_id' => $this->usdCurrency->id,
        'min_proposal_points' => 0
    ]);

    expect($client->fresh()->points_balance)->toBe(25); // Cost is 25 base
});

it('allows a user to post a job with new custom skills', function () {
    $client = User::factory()->create([
        'points_balance' => 50, 
        'currency_id' => $this->usdCurrency->id,
        'can_add_freelance_skills' => true,
        'onboarding_completed' => true
    ]);
    
    $payload = [
        'title' => 'Job with custom skill',
        'description' => 'Test',
        'budget' => 500,
        'currency_id' => $this->usdCurrency->id,
        'min_proposal_points' => 0,
        'type' => 'fixed',
        'duration' => '1_month',
        'skills' => ['New Custom Skill'] // passing string instead of ID
    ];

    $response = $this->actingAs($client)->post('/freelance/jobs', $payload);

    $response->assertRedirect(route('freelance.my-jobs'));

    $this->assertDatabaseHas('freelance_skills', [
        'name' => 'New Custom Skill',
        'status' => 'pending',
        'created_by' => $client->id
    ]);

    $job = Job::where('title', 'Job with custom skill')->first();
    expect($job->skills->first()->name)->toBe('New Custom Skill');
});

it('prevents posting a job with a rejected skill', function () {
    $client = User::factory()->create([
        'points_balance' => 50, 
        'currency_id' => $this->usdCurrency->id,
        'onboarding_completed' => true
    ]);
    $rejectedSkill = Skill::create(['name' => 'BadSkill', 'status' => 'rejected', 'created_by' => null]);
    
    $payload = [
        'title' => 'Job with rejected skill',
        'description' => 'Test',
        'budget' => 500,
        'currency_id' => $this->usdCurrency->id,
        'min_proposal_points' => 0,
        'type' => 'fixed',
        'duration' => '1_month',
        'skills' => [$rejectedSkill->id]
    ];

    $response = $this->actingAs($client)->post('/freelance/jobs', $payload);

    $response->assertSessionHasErrors(['skills']);
});

it('prevents user from adding custom skills if blocked', function () {
    $client = User::factory()->create([
        'points_balance' => 50, 
        'currency_id' => $this->usdCurrency->id,
        'can_add_freelance_skills' => false,
        'onboarding_completed' => true
    ]);
    
    $payload = [
        'title' => 'Blocked Custom Skill Job',
        'description' => 'Test',
        'budget' => 500,
        'currency_id' => $this->usdCurrency->id,
        'min_proposal_points' => 0,
        'type' => 'fixed',
        'duration' => '1_month',
        'skills' => ['Blocked Skill']
    ];

    $response = $this->actingAs($client)->post('/freelance/jobs', $payload);

    $response->assertSessionHasErrors(['skills']);
    $this->assertDatabaseMissing('freelance_skills', ['name' => 'Blocked Skill']);
});
