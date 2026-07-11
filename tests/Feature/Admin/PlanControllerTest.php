<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $clientUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create(['onboarding_completed' => true]);
        $this->admin->assignRole('admin');

        $this->clientUser = User::factory()->create(['onboarding_completed' => true]);
        $this->clientUser->assignRole('client');
    }

    public function test_admin_can_view_plans_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.plans.index'));
        $response->assertStatus(200);
    }

    public function test_non_admin_cannot_view_plans_index(): void
    {
        $response = $this->actingAs($this->clientUser)->get(route('admin.plans.index'));
        $response->assertStatus(403);
    }
}
