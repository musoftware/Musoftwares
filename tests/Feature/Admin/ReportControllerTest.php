<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ReportControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected User $clientUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create(['onboarding_completed' => true]);
        $this->admin->assignRole('admin');

        $this->clientUser = User::factory()->create(['onboarding_completed' => true]);
        $this->clientUser->assignRole('client');
    }

    public function test_admin_can_view_reports_index(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/reports');
        $response->assertStatus(200);
    }

    public function test_non_admin_cannot_view_reports_index(): void
    {
        $response = $this->actingAs($this->clientUser)->get('/admin/reports');
        $response->assertStatus(403);
    }

    public function test_admin_reports_index_validation_fails_with_invalid_dates(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/reports?from=invalid-date&to=invalid-date');
        $response->assertSessionHasErrors(['from', 'to']);
    }

    public function test_admin_reports_index_validation_fails_if_to_is_before_from(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/reports?from=2024-02-01&to=2024-01-01');
        $response->assertSessionHasErrors(['to']);
    }

    public function test_admin_reports_index_with_valid_dates(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/reports?from=2024-01-01&to=2024-02-01');
        $response->assertStatus(200);
    }
}
