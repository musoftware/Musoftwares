<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HoursCalendarControllerTest extends TestCase
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
        $response->assertJsonStructure([
            'heatmap',
            'chart_30_days',
            'market_hourly_rate',
            'recommended_hourly_rate',
            'business_currency',
            'last_30_days_timers',
        ]);
    }

    public function test_non_admin_cannot_get_hours_calendar_data(): void
    {
        $response = $this->actingAs($this->clientUser)->post('/admin/hours-calendar/data', [
            'year' => date('Y'),
        ]);

        $response->assertStatus(403);
    }
}
