<?php

namespace Tests\Feature\Admin;

use App\Models\SerialDevice;
use App\Models\SerialUserDevice;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SerialUserDeviceControllerTest extends TestCase
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
            'notes' => 'Test assignment',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('serial_user_devices', [
            'user_id' => $this->clientUser->id,
            'device_id' => $device->device_id,
            'notes' => 'Test assignment',
        ]);
    }

    public function test_admin_can_update_status()
    {
        $device = SerialDevice::factory()->create(['device_id' => 'DEV123', 'status' => 'active', 'machine_name' => 'Machine 1']);
        $assignment = SerialUserDevice::create([
            'user_id' => $this->clientUser->id,
            'device_id' => $device->device_id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin)->patch("/admin/serial-user-devices/{$assignment->id}/status", [
            'status' => 'inactive',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('serial_user_devices', [
            'id' => $assignment->id,
            'status' => 'inactive',
        ]);
    }

    public function test_admin_can_delete_assignment()
    {
        $device = SerialDevice::factory()->create(['device_id' => 'DEV123', 'status' => 'active', 'machine_name' => 'Machine 1']);
        $assignment = SerialUserDevice::create([
            'user_id' => $this->clientUser->id,
            'device_id' => $device->device_id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin)->delete("/admin/serial-user-devices/{$assignment->id}");

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertSoftDeleted('serial_user_devices', [
            'id' => $assignment->id,
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
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin)->patch("/admin/serial-user-devices/users/{$this->clientUser->id}/status", [
            'status' => 'inactive',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('serial_user_devices', [
            'id' => $assignment->id,
            'status' => 'inactive',
        ]);
    }

    public function test_admin_can_update_user_temp_valid()
    {
        $date = now()->addDays(7)->format('Y-m-d H:i:s');

        $response = $this->actingAs($this->admin)->patch("/admin/serial-user-devices/users/{$this->clientUser->id}/temp-valid", [
            'temp_valid_until' => $date,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('users', [
            'id' => $this->clientUser->id,
            'temp_valid_until' => $date,
        ]);
    }

    public function test_admin_can_allocate_and_deallocate_software_to_reseller()
    {
        $software = \App\Models\SerialSoftware::create([
            'name' => 'ClinicPro',
            'default_status' => 'active',
        ]);

        $reseller = User::factory()->create(['onboarding_completed' => true]);
        $reseller->assignRole('software_reseller');

        // Allocate software with max 10 devices
        $response = $this->actingAs($this->admin)->post("/admin/users/{$reseller->id}/reseller-softwares", [
            'serial_software_id' => $software->id,
            'max_devices' => 10,
            'notes' => 'Authorized distributor for Cairo',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('serial_software_resellers', [
            'user_id' => $reseller->id,
            'serial_software_id' => $software->id,
            'max_devices' => 10,
            'status' => 'active',
        ]);

        $allocation = \App\Models\SerialSoftwareReseller::where('user_id', $reseller->id)
            ->where('serial_software_id', $software->id)
            ->first();

        // Deallocate software
        $deleteResponse = $this->actingAs($this->admin)->delete("/admin/users/{$reseller->id}/reseller-softwares/{$allocation->id}");
        $deleteResponse->assertRedirect();
        $deleteResponse->assertSessionHas('success');
        $this->assertDatabaseMissing('serial_software_resellers', [
            'id' => $allocation->id,
        ]);
    }

    public function test_can_toggle_reseller_software_scope_and_filters_per_software(): void
    {
        $softwareA = \App\Models\SerialSoftware::create(['name' => 'AppAlpha', 'default_status' => 'active']);
        $softwareB = \App\Models\SerialSoftware::create(['name' => 'AppBeta', 'default_status' => 'active']);

        $reseller = User::factory()->create(['onboarding_completed' => true]);
        $reseller->assignRole('software_reseller');

        // Allocate software A with can_view_all_devices = true
        $this->actingAs($this->admin)->post("/admin/users/{$reseller->id}/reseller-softwares", [
            'serial_software_id' => $softwareA->id,
            'max_devices' => 50,
            'can_view_all_devices' => true,
        ]);

        $allocA = \App\Models\SerialSoftwareReseller::where('user_id', $reseller->id)
            ->where('serial_software_id', $softwareA->id)
            ->first();
        $this->assertTrue((bool) $allocA->can_view_all_devices);

        // Toggle scope of software A
        $toggleRes = $this->actingAs($this->admin)->patch("/admin/users/{$reseller->id}/reseller-softwares/{$allocA->id}/toggle-scope");
        $toggleRes->assertRedirect();
        $allocA->refresh();
        $this->assertFalse((bool) $allocA->can_view_all_devices);

        // Toggle back to true
        $this->actingAs($this->admin)->patch("/admin/users/{$reseller->id}/reseller-softwares/{$allocA->id}/toggle-scope");
        $allocA->refresh();
        $this->assertTrue((bool) $allocA->can_view_all_devices);

        // Allocate software B with can_view_all_devices = false
        $this->actingAs($this->admin)->post("/admin/users/{$reseller->id}/reseller-softwares", [
            'serial_software_id' => $softwareB->id,
            'max_devices' => 50,
            'can_view_all_devices' => false,
        ]);

        $allocB = \App\Models\SerialSoftwareReseller::where('user_id', $reseller->id)
            ->where('serial_software_id', $softwareB->id)
            ->first();
        $this->assertFalse((bool) $allocB->can_view_all_devices);

        // Device for Software A owned by ANOTHER user
        $otherUserDevice = \App\Models\SerialUserDevice::create([
            'user_id' => User::factory()->create()->id,
            'device_id' => 'HWID-ALPHA-ALL',
            'status' => 'active',
            'reseller_id' => null,
        ]);
        \App\Models\SerialDevice::create([
            'serial_software_id' => $softwareA->id,
            'device_id' => 'HWID-ALPHA-ALL',
            'status' => 'active',
        ]);

        // Device for Software B owned by ANOTHER user (should NOT be visible to reseller)
        $otherBetaDevice = \App\Models\SerialUserDevice::create([
            'user_id' => User::factory()->create()->id,
            'device_id' => 'HWID-BETA-HIDDEN',
            'status' => 'active',
            'reseller_id' => null,
        ]);
        \App\Models\SerialDevice::create([
            'serial_software_id' => $softwareB->id,
            'device_id' => 'HWID-BETA-HIDDEN',
            'status' => 'active',
        ]);

        // Device for Software B owned by THIS reseller (SHOULD be visible)
        $myBetaDevice = \App\Models\SerialUserDevice::create([
            'user_id' => User::factory()->create()->id,
            'device_id' => 'HWID-BETA-MINE',
            'status' => 'active',
            'reseller_id' => $reseller->id,
        ]);
        \App\Models\SerialDevice::create([
            'serial_software_id' => $softwareB->id,
            'device_id' => 'HWID-BETA-MINE',
            'status' => 'active',
        ]);

        $service = app(\App\Services\ResellerDeviceService::class);
        $devices = $service->getResellerDevices($reseller, []);
        $deviceIds = collect($devices->items())->pluck('device_id')->toArray();

        // Software A device is visible because Software A has can_view_all_devices = true
        $this->assertContains('HWID-ALPHA-ALL', $deviceIds);

        // Software B foreign device is NOT visible because Software B has can_view_all_devices = false
        $this->assertNotContains('HWID-BETA-HIDDEN', $deviceIds);

        // Software B reseller-assigned device IS visible
        $this->assertContains('HWID-BETA-MINE', $deviceIds);
    }
}
