<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Modules\Freelance\Models\Skill;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class FreelanceSkillControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected User $clientUser;
    protected Skill $skill;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create(['onboarding_completed' => true]);
        $this->admin->assignRole('admin');

        $this->clientUser = User::factory()->create(['onboarding_completed' => true]);
        $this->clientUser->assignRole('client');

        // We assume creator_id could be filled or default if not fillable it still works without it depending on migration, but let's just make it simple.
        $this->skill = new Skill([
            'name' => 'PHP',
            'description' => 'PHP programming language'
        ]);
        $this->skill->status = 'pending';
        // 'created_by' might not be in fillable.
        $this->skill->created_by = $this->clientUser->id;
        $this->skill->save();
    }

    public function test_admin_can_view_skills_index(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/freelance/skills');
        $response->assertStatus(200);
    }

    public function test_non_admin_cannot_view_skills_index(): void
    {
        $response = $this->actingAs($this->clientUser)->get('/admin/freelance/skills');
        $response->assertStatus(403);
    }

    public function test_admin_can_store_skill(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/freelance/skills', [
            'name' => 'Laravel',
            'description' => 'Laravel framework'
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('freelance_skills', ['name' => 'Laravel']);
    }

    public function test_admin_can_update_skill(): void
    {
        $response = $this->actingAs($this->admin)->put("/admin/freelance/skills/{$this->skill->id}", [
            'name' => 'PHP 8',
            'description' => 'Updated PHP'
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('freelance_skills', ['name' => 'PHP 8']);
    }

    public function test_admin_can_approve_skill(): void
    {
        $response = $this->actingAs($this->admin)->post("/admin/freelance/skills/{$this->skill->id}/approve");

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertEquals('approved', $this->skill->fresh()->status);
    }

    public function test_admin_can_reject_skill(): void
    {
        $response = $this->actingAs($this->admin)->post("/admin/freelance/skills/{$this->skill->id}/reject");

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertEquals('rejected', $this->skill->fresh()->status);
    }

    public function test_admin_can_block_user_from_skills(): void
    {
        $response = $this->actingAs($this->admin)->post("/admin/freelance/skills/block-user/{$this->clientUser->id}");

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertFalse((bool) $this->clientUser->fresh()->can_add_freelance_skills);
    }

    public function test_admin_can_delete_skill(): void
    {
        $response = $this->actingAs($this->admin)->delete("/admin/freelance/skills/{$this->skill->id}");

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('freelance_skills', ['id' => $this->skill->id]);
    }
}
