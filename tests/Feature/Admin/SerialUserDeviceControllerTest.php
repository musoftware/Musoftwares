<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\SerialDevice;
use App\Models\SerialUserDevice;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SerialUserDeviceControllerTest extends TestCase
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

    public function test_admin_can_view_serial_user_devices_index()
    {
        $response = $this->actingAs($this->admin)->get('/admin/serial-user-devices');
        $response->assertStatus(200);
    }

    public function test_non_admin_cannot_view_serial_user_devices_index()
    {
        $response = $this->actingAs($this->clientUser)->get('/admin/serial-user-devices');
        $response->assertStatus(403);
    }

    public function test_admin_can_view_assign_page()
    {
        $response = $this->actingAs($this->admin)->get('/admin/serial-user-devices/assign');
        $response->assertStatus(200);
    }

    public function test_admin_can_store_serial_user_device()
    {
        $device = SerialDevice::factory()->create(['device_id' => 'DEV123', 'status' => 'active', 'machine_name' => 'Machine 1']);

        $response = $this->actingAs($this->admin)->post('/admin/serial-user-devices', [
            'user_id' => $this->clientUser->id,
            'device_id' => $device->device_id,
            'status' => 'active',
            'notes' => 'Test assignment'
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('serial_user_devices', [
            'user_id' => $this->clientUser->id,
            'device_id' => $device->device_id,
            'notes' => 'Test assignment'
        ]);
    }

    public function test_admin_can_update_status()
    {
        $device = SerialDevice::factory()->create(['device_id' => 'DEV123', 'status' => 'active', 'machine_name' => 'Machine 1']);
        $assignment = SerialUserDevice::create([
            'user_id' => $this->clientUser->id,
            'device_id' => $device->device_id,
            'status' => 'active'
        ]);

        $response = $this->actingAs($this->admin)->patch("/admin/serial-user-devices/{$assignment->id}/status", [
            'status' => 'inactive'
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('serial_user_devices', [
            'id' => $assignment->id,
            'status' => 'inactive'
        ]);
    }

    public function test_admin_can_delete_assignment()
    {
        $device = SerialDevice::factory()->create(['device_id' => 'DEV123', 'status' => 'active', 'machine_name' => 'Machine 1']);
        $assignment = SerialUserDevice::create([
            'user_id' => $this->clientUser->id,
            'device_id' => $device->device_id,
            'status' => 'active'
        ]);

        $response = $this->actingAs($this->admin)->delete("/admin/serial-user-devices/{$assignment->id}");

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('serial_user_devices', [
            'id' => $assignment->id
        ]);
    }

    public function test_admin_can_view_by_user()
    {
        $response = $this->actingAs($this->admin)->get('/admin/serial-user-devices/by-user');
        $response->assertStatus(200);
    }

    public function test_admin_can_update_user_status()
    {
        $device = SerialDevice::factory()->create(['device_id' => 'DEV123', 'status' => 'active', 'machine_name' => 'Machine 1']);
        $assignment = SerialUserDevice::create([
            'user_id' => $this->clientUser->id,
            'device_id' => $device->device_id,
            'status' => 'active'
        ]);

        $response = $this->actingAs($this->admin)->patch("/admin/serial-user-devices/users/{$this->clientUser->id}/status", [
            'status' => 'inactive'
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('serial_user_devices', [
            'id' => $assignment->id,
            'status' => 'inactive'
        ]);
    }

    public function test_admin_can_update_user_temp_valid()
    {
        $date = now()->addDays(7)->format('Y-m-d H:i:s');
        
        $response = $this->actingAs($this->admin)->patch("/admin/serial-user-devices/users/{$this->clientUser->id}/temp-valid", [
            'temp_valid_until' => $date
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('users', [
            'id' => $this->clientUser->id,
            'temp_valid_until' => $date
        ]);
    }
}
