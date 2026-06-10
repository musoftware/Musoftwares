<?php

namespace Tests\Feature\Freelance;

use Modules\Freelance\Models\Skill;
use Modules\Freelance\Models\FreelanceProfile;

class ProfileAndSkillTest extends FreelanceTestCase
{
    public function test_freelancer_can_update_profile(): void
    {
        $response = $this->actingAs($this->freelancer1)
            ->put(route('freelance.profile.update'), [
                'title' => 'Senior Developer',
                'bio' => '10 years of experience in PHP',
                'hourly_rate' => 50.00,
            ]);

        $response->assertStatus(302);

        $this->assertDatabaseHas('freelance_profiles', [
            'user_id' => $this->freelancer1->id,
            'title' => 'Senior Developer',
            'hourly_rate' => 50.00,
        ]);
    }

    public function test_freelancer_can_add_skill_and_admin_approves(): void
    {
        // Add skill
        // The freelance.skills.store route is for adding custom skills 
        // to the global skills table if it doesn't exist, which goes to 'pending' status
        $response = $this->actingAs($this->freelancer1)
            ->post(route('freelance.skills.store'), [
                'name' => 'VueJS',
            ]);

        $response->assertStatus(302);

        $this->assertDatabaseHas('freelance_skills', [
            'name' => 'VueJS',
            'status' => 'pending',
        ]);

        $skill = Skill::where('name', 'VueJS')->first();

        // Admin approves skill
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.freelance.skills.approve', $skill->id));

        $this->assertContains($response->status(), [200, 302]);
        $this->assertEquals('approved', $skill->fresh()->status);
        
        // Freelancer can attach approved skill to their profile
        $response = $this->actingAs($this->freelancer1)
            ->post(route('freelance.user-skills.store'), [
                'skill_id' => $skill->id,
            ]);

        $response->assertStatus(302);

        $this->assertDatabaseHas('freelance_skill_user', [
            'user_id' => $this->freelancer1->id,
            'skill_id' => $skill->id,
        ]);
    }
}
