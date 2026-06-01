<?php

uses(\Tests\TestCase::class, \Illuminate\Foundation\Testing\DatabaseTransactions::class);

use Modules\Freelance\Models\Skill;
use App\Models\User;

use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin']);
    
    $this->admin = User::factory()->create(['onboarding_completed' => true]);
    $this->admin->assignRole('admin');

    $this->freelancer = User::factory()->create(['onboarding_completed' => true]);
});

it('allows admin to view freelance skills', function () {
    if (config('database.default') === 'sqlite' || env('DB_CONNECTION') === 'sqlite') {
        test()->markTestSkipped('SQLite does not support FIELD() function used in sorting.');
    }

    Skill::create(['name' => 'Pending Skill', 'status' => 'pending']);

    $response = $this->actingAs($this->admin)->get(route('admin.freelance.skills.index'));

    $response->assertStatus(200);
});

it('allows admin to approve a pending skill', function () {
    $skill = Skill::create(['name' => 'Pending Skill', 'status' => 'pending']);

    $response = $this->actingAs($this->admin)->post(route('admin.freelance.skills.approve', $skill->id));

    $response->assertRedirect(route('admin.freelance.skills.index'));
    expect($skill->fresh()->status)->toBe('approved');
});

it('allows admin to reject a pending skill', function () {
    $skill = Skill::create(['name' => 'Bad Skill', 'status' => 'pending']);

    $response = $this->actingAs($this->admin)->post(route('admin.freelance.skills.reject', $skill->id));

    $response->assertRedirect(route('admin.freelance.skills.index'));
    expect($skill->fresh()->status)->toBe('rejected');
});

it('allows admin to block a user from adding skills', function () {
    $user = User::factory()->create(['can_add_freelance_skills' => true]);

    $response = $this->actingAs($this->admin)->post(route('admin.freelance.skills.block-user', $user->id));

    $response->assertRedirect(route('admin.freelance.skills.index'));
    expect($user->fresh()->can_add_freelance_skills)->toBeFalse();
});

it('hides rejected skills from normal users API response', function () {
    Skill::create(['name' => 'Good Skill', 'status' => 'approved']);
    Skill::create(['name' => 'Bad Skill', 'status' => 'rejected']);
    
    $userSkill = Skill::create(['name' => 'My Pending Skill', 'status' => 'pending', 'created_by' => $this->freelancer->id]);

    $response = $this->actingAs($this->freelancer)->getJson('/freelance/skills');

    $response->assertStatus(200);
    
    $skills = $response->json();
    $names = collect($skills)->pluck('name');

    expect($names)->toContain('Good Skill');
    expect($names)->toContain('My Pending Skill');
    expect($names)->not->toContain('Bad Skill');
});
