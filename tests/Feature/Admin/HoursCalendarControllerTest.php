<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class HoursCalendarControllerTest extends TestCase
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

    public function test_admin_can_view_hours_calendar_index(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/hours-calendar');
        $response->assertStatus(200);
    }

    public function test_non_admin_cannot_view_hours_calendar_index(): void
    {
        $response = $this->actingAs($this->clientUser)->get('/admin/hours-calendar');
        $response->assertStatus(403);
    }

    public function test_admin_can_get_hours_calendar_data(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/hours-calendar/data', [
            'year' => date('Y'),
        ]);

        $response->assertStatus(200);
        $response->assertJsonIsArray();
    }

    public function test_non_admin_cannot_get_hours_calendar_data(): void
    {
        $response = $this->actingAs($this->clientUser)->post('/admin/hours-calendar/data', [
            'year' => date('Y'),
        ]);

        $response->assertStatus(403);
    }
}
