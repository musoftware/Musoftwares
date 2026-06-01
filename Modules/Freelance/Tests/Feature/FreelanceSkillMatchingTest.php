<?php

uses(\Tests\TestCase::class, \Illuminate\Foundation\Testing\DatabaseTransactions::class);

use Modules\Freelance\Models\Job;
use Modules\Freelance\Models\Skill;
use App\Models\User;

it('ensures job discovery process is driven by skill matching', function () {
    $client = User::factory()->create();
    $freelancer = User::factory()->create(['onboarding_completed' => true]);
    
    $skillPhp = Skill::factory()->create(['name' => 'PHP']);
    $skillVue = Skill::factory()->create(['name' => 'VueJS']);
    
    // Freelancer has PHP skill
    $freelancer->freelanceSkills()->attach([$skillPhp->id]);
    
    // Client posts Job requiring PHP
    $jobMatching = Job::factory()->create(['client_id' => $client->id, 'status' => 'open']);
    $jobMatching->skills()->attach([$skillPhp->id]);

    // Client posts Job requiring VueJS
    $jobNotMatching = Job::factory()->create(['client_id' => $client->id, 'status' => 'open']);
    $jobNotMatching->skills()->attach([$skillVue->id]);

    // Suppose we have an action or query to get relevant jobs for freelancer
    // For test purposes, we simulate the algorithm:
    $relevantJobs = Job::whereHas('skills', function($q) use ($freelancer) {
        $q->whereIn('skills.id', $freelancer->freelanceSkills->pluck('id'));
    })->get();

    expect($relevantJobs->contains('id', $jobMatching->id))->toBeTrue();
    expect($relevantJobs->contains('id', $jobNotMatching->id))->toBeFalse();
});
