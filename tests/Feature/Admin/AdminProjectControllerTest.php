<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Project;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AdminProjectControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    private function createAdmin()
    {
        $admin = User::factory()->create(['onboarding_completed' => true]);
        $admin->assignRole('admin');
        return $admin;
    }

    private function createClient()
    {
        $client = User::factory()->create(['onboarding_completed' => true]);
        $client->assignRole('client');
        return $client;
    }

    public function test_admin_can_access_projects_index()
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('admin.projects.index'));

        $response->assertSuccessful();
    }

    public function test_non_admin_cannot_access_projects_index()
    {
        $client = $this->createClient();

        $response = $this->actingAs($client)->get(route('admin.projects.index'));

        $response->assertStatus(403);
    }

    public function test_admin_can_store_project()
    {
        $admin = $this->createAdmin();
        $client = $this->createClient();

        $response = $this->actingAs($admin)->post(route('admin.projects.store'), [
            'project_name' => 'New Test Project',
            'user_id' => $client->id,
            'project_status' => 'pending',
            'date_start' => now()->format('Y-m-d'),
            'date_end' => now()->addDays(10)->format('Y-m-d'),
            'hour_rate' => 0,
        ]);

        $response->assertRedirect();
        
        $this->assertDatabaseHas('projects', [
            'project_name' => 'New Test Project',
            'user_id' => $client->id,
        ]);
    }
}
