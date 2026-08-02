<?php

namespace Tests\Feature\Admin;

use App\Models\SerialDevice;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SerialDeviceControllerTest extends TestCase
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

    public function test_admin_can_view_serial_devices_index()
    {
        $response = $this->actingAs($this->admin)->get('/admin/serial-devices');
        $response->assertStatus(200);
    }

    public function test_non_admin_cannot_view_serial_devices_index()
    {
        $response = $this->actingAs($this->clientUser)->get('/admin/serial-devices');
        $response->assertStatus(403);
    }

    public function test_admin_can_update_serial_device_status()
    {
        $device = SerialDevice::factory()->create([
            'device_id' => 'DEV123',
            'status' => 'active',
            'machine_name' => 'Machine 1',
        ]);

        $response = $this->actingAs($this->admin)->patch("/admin/serial-devices/{$device->id}/status", [
            'status' => 'inactive',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('serial_devices', [
            'id' => $device->id,
            'status' => 'inactive',
        ]);
    }

    public function test_admin_can_delete_serial_device()
    {
        $device = SerialDevice::factory()->create([
            'device_id' => 'DEV123',
            'status' => 'active',
            'machine_name' => 'Machine 1',
        ]);

        $response = $this->actingAs($this->admin)->delete("/admin/serial-devices/{$device->id}");

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertSoftDeleted('serial_devices', [
            'id' => $device->id,
        ]);
    }

    public function test_admin_can_export_serial_devices()
    {
        $response = $this->actingAs($this->admin)->get('/admin/serial-devices/export');
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    public function test_admin_can_bulk_update_status()
    {
        $device1 = SerialDevice::factory()->create(['device_id' => 'DEV1', 'status' => 'active', 'machine_name' => 'Machine 1']);
        $device2 = SerialDevice::factory()->create(['device_id' => 'DEV2', 'status' => 'active', 'machine_name' => 'Machine 2']);

        $response = $this->actingAs($this->admin)->post('/admin/serial-devices/bulk-status', [
            'ids' => [$device1->id, $device2->id],
            'status' => 'inactive',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('serial_devices', ['id' => $device1->id, 'status' => 'inactive']);
        $this->assertDatabaseHas('serial_devices', ['id' => $device2->id, 'status' => 'inactive']);
    }

    public function test_admin_can_bulk_delete()
    {
        $device1 = SerialDevice::factory()->create(['device_id' => 'DEV1', 'status' => 'active', 'machine_name' => 'Machine 1']);
        $device2 = SerialDevice::factory()->create(['device_id' => 'DEV2', 'status' => 'active', 'machine_name' => 'Machine 2']);

        $response = $this->actingAs($this->admin)->post('/admin/serial-devices/bulk-delete', [
            'ids' => [$device1->id, $device2->id],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertSoftDeleted('serial_devices', ['id' => $device1->id]);
        $this->assertSoftDeleted('serial_devices', ['id' => $device2->id]);
    }
}
