<?php

uses(\Tests\TestCase::class, \Illuminate\Foundation\Testing\RefreshDatabase::class);

use App\Models\User;
use Modules\Freelance\Models\FreelanceProfile;

it('allows anyone to browse public freelancers', function () {
    $user1 = User::factory()->create(['name' => 'Alice']);
    FreelanceProfile::create([
        'user_id' => $user1->id,
        'title' => 'Frontend Dev',
        'bio' => 'VueJS expert',
        'hourly_rate' => 30
    ]);

    $user2 = User::factory()->create(['name' => 'Bob']);
    FreelanceProfile::create([
        'user_id' => $user2->id,
        'title' => 'Backend Dev',
        'bio' => 'Laravel expert',
        'hourly_rate' => 50
    ]);

    $response = $this->get('/freelance/freelancers/browse');

    $response->assertStatus(200);
});

it('can filter freelancers by search query', function () {
    $user1 = User::factory()->create(['name' => 'Alice']);
    FreelanceProfile::create([
        'user_id' => $user1->id,
        'title' => 'Frontend Dev',
    ]);

    $response = $this->get('/freelance/freelancers/browse?search=Frontend');

    $response->assertStatus(200);
});

it('can filter freelancers by hourly rate', function () {
    $user1 = User::factory()->create(['name' => 'Alice']);
    FreelanceProfile::create([
        'user_id' => $user1->id,
        'hourly_rate' => 20,
    ]);

    $user2 = User::factory()->create(['name' => 'Bob']);
    FreelanceProfile::create([
        'user_id' => $user2->id,
        'hourly_rate' => 80,
    ]);

    $response = $this->get('/freelance/freelancers/browse?rate_min=50');
    $response->assertStatus(200);
});
