<?php

namespace Tests\Feature\Admin;

use App\Models\RecurringBusyTime;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminBusyTimesControllerTest extends TestCase
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

    public function test_admin_can_view_busy_times_index(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/busy-times');
        $response->assertStatus(200);
    }

    public function test_admin_can_filter_busy_times_index(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/busy-times?user_id='.$this->clientUser->id.'&is_active=1&is_recurring=1&day_of_week=1');
        $response->assertStatus(200);
    }

    public function test_non_admin_cannot_view_busy_times_index(): void
    {
        $response = $this->actingAs($this->clientUser)->get('/admin/busy-times');
        $response->assertStatus(403);
    }

    public function test_admin_can_toggle_active_status(): void
    {
        $busyTime = RecurringBusyTime::create([
            'user_id' => $this->clientUser->id,
            'is_recurring' => true,
            'day_of_week' => 1,
            'start_time' => '09:00',
            'end_time' => '17:00',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->post("/admin/busy-times/{$busyTime->id}/toggle-active");

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertFalse($busyTime->fresh()->is_active);
    }

    public function test_non_admin_cannot_toggle_active_status(): void
    {
        $busyTime = RecurringBusyTime::create([
            'user_id' => $this->clientUser->id,
            'is_recurring' => true,
            'day_of_week' => 1,
            'start_time' => '09:00',
            'end_time' => '17:00',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->clientUser)->post("/admin/busy-times/{$busyTime->id}/toggle-active");
        $response->assertStatus(403);
    }

    public function test_admin_can_destroy_busy_time(): void
    {
        $busyTime = RecurringBusyTime::create([
            'user_id' => $this->clientUser->id,
            'is_recurring' => true,
            'day_of_week' => 1,
            'start_time' => '09:00',
            'end_time' => '17:00',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->delete("/admin/busy-times/{$busyTime->id}");

        $response->assertRedirect(route('admin.busy-times.index'));
        $response->assertSessionHas('success');

        $this->assertSoftDeleted('recurring_busy_times', ['id' => $busyTime->id]);
    }

    public function test_non_admin_cannot_destroy_busy_time(): void
    {
        $busyTime = RecurringBusyTime::create([
            'user_id' => $this->clientUser->id,
            'is_recurring' => true,
            'day_of_week' => 1,
            'start_time' => '09:00',
            'end_time' => '17:00',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->clientUser)->delete("/admin/busy-times/{$busyTime->id}");
        $response->assertStatus(403);
    }
}
