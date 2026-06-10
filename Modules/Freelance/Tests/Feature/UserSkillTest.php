<?php

uses(\Tests\TestCase::class, \Illuminate\Foundation\Testing\RefreshDatabase::class);

use App\Models\User;
use Modules\Freelance\Models\Skill;

it('allows an authenticated user to add a skill to their profile', function () {
    $this->withoutMiddleware([\App\Http\Middleware\EnsureOnboardingCompleted::class, \App\Http\Middleware\SubscriptionMiddleware::class]);

    $user = User::factory()->create();
    $skill = Skill::factory()->create(['status' => 'approved']);

    $response = $this->actingAs($user)->post('/freelance/user-skills', [
        'skill_id' => $skill->id,
    ]);

    $response->assertSessionHas('success');
    $response->assertRedirect();

    $this->assertDatabaseHas('freelance_user_skills', [
        'user_id' => $user->id,
        'skill_id' => $skill->id,
    ]);
});

it('allows an authenticated user to remove a skill from their profile', function () {
    $this->withoutMiddleware([\App\Http\Middleware\EnsureOnboardingCompleted::class, \App\Http\Middleware\SubscriptionMiddleware::class]);

    $user = User::factory()->create();
    $skill = Skill::factory()->create(['status' => 'approved']);
    
    $user->freelanceSkills()->attach($skill->id);

    $this->assertDatabaseHas('freelance_user_skills', [
        'user_id' => $user->id,
        'skill_id' => $skill->id,
    ]);

    $response = $this->actingAs($user)->delete("/freelance/user-skills/{$skill->id}");

    $response->assertSessionHas('success');
    $response->assertRedirect();

    $this->assertDatabaseMissing('freelance_user_skills', [
        'user_id' => $user->id,
        'skill_id' => $skill->id,
    ]);
});
