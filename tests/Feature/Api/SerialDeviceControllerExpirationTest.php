<?php

namespace Tests\Feature\Api;

use App\Models\SerialDevice;
use App\Models\SerialSoftware;
use App\Models\SerialUserDevice;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SerialDeviceControllerExpirationTest extends TestCase
{
    use RefreshDatabase;

    protected SerialSoftware $software;

    protected User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->software = SerialSoftware::create([
            'name' => 'MegaPos2026',
            'default_status' => 'active',
        ]);

        $this->customer = User::factory()->create([
            'email' => 'client@pos.com',
            'onboarding_completed' => true,
        ]);
    }

    public function test_active_unexpired_device_returns_active_status()
    {
        $device = SerialDevice::create([
            'serial_software_id' => $this->software->id,
            'device_id' => 'DEVICE-VALID-100',
            'status' => 'active',
        ]);

        SerialUserDevice::create([
            'user_id' => $this->customer->id,
            'device_id' => 'DEVICE-VALID-100',
            'status' => 'active',
            'expires_at' => now()->addMonth(),
        ]);

        $response = $this->postJson('/api/serial/device', [
            'program_name' => 'MegaPos2026',
            'device_id' => 'DEVICE-VALID-100',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'active',
            'has_linked_user' => true,
            'is_expired' => false,
        ]);
    }

    public function test_expired_device_returns_inactive_status()
    {
        $device = SerialDevice::create([
            'serial_software_id' => $this->software->id,
            'device_id' => 'DEVICE-EXPIRED-200',
            'status' => 'active',
        ]);

        SerialUserDevice::create([
            'user_id' => $this->customer->id,
            'device_id' => 'DEVICE-EXPIRED-200',
            'status' => 'active',
            'expires_at' => now()->subDay(), // expired yesterday
        ]);

        $response = $this->postJson('/api/serial/device', [
            'program_name' => 'MegaPos2026',
            'device_id' => 'DEVICE-EXPIRED-200',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'inactive',
            'has_linked_user' => true,
            'is_expired' => true,
        ]);
    }

    public function test_expired_device_with_user_temp_valid_until_remains_active()
    {
        $this->customer->update([
            'temp_valid_until' => now()->addDays(3), // grace period active
        ]);

        $device = SerialDevice::create([
            'serial_software_id' => $this->software->id,
            'device_id' => 'DEVICE-OVERRIDE-300',
            'status' => 'active',
        ]);

        SerialUserDevice::create([
            'user_id' => $this->customer->id,
            'device_id' => 'DEVICE-OVERRIDE-300',
            'status' => 'active',
            'expires_at' => now()->subDays(2), // expired 2 days ago
        ]);

        $response = $this->postJson('/api/serial/device', [
            'program_name' => 'MegaPos2026',
            'device_id' => 'DEVICE-OVERRIDE-300',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'active',
            'has_linked_user' => true,
            'is_expired' => false,
        ]);
    }

    public function test_paid_software_without_license_deactivates_and_returns_inactive(): void
    {
        $this->software->update([
            'requires_payment' => true,
            'price' => 20.00,
            'currency' => 'USD',
        ]);

        // Device existed as active from before software was marked as paid
        $device = SerialDevice::create([
            'serial_software_id' => $this->software->id,
            'device_id' => 'DEVICE-PAID-TEST-1',
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/serial/device', [
            'program_name' => 'MegaPos2026',
            'device_id' => 'DEVICE-PAID-TEST-1',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'inactive',
            'requires_payment' => true,
            'price' => 20.00,
            'currency' => 'USD',
            'has_active_license' => false,
        ]);

        // Assert database record was automatically synced to inactive
        $this->assertDatabaseHas('serial_devices', [
            'id' => $device->id,
            'status' => 'inactive',
        ]);
    }

    public function test_paid_software_with_active_license_returns_active(): void
    {
        $this->software->update([
            'requires_payment' => true,
            'price' => 20.00,
            'currency' => 'USD',
        ]);

        $device = SerialDevice::create([
            'serial_software_id' => $this->software->id,
            'device_id' => 'DEVICE-PAID-TEST-2',
            'status' => 'inactive',
        ]);

        // Link device to customer
        SerialUserDevice::create([
            'user_id' => $this->customer->id,
            'device_id' => 'DEVICE-PAID-TEST-2',
            'status' => 'inactive',
        ]);

        // Grant active license to customer
        \App\Models\SerialSoftwareLicense::create([
            'user_id' => $this->customer->id,
            'serial_software_id' => $this->software->id,
            'status' => 'active',
            'expires_at' => now()->addMonth(),
        ]);

        $response = $this->postJson('/api/serial/device', [
            'program_name' => 'MegaPos2026',
            'device_id' => 'DEVICE-PAID-TEST-2',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'active',
            'has_active_license' => true,
            'requires_payment' => true,
        ]);

        // Assert device in database was activated
        $this->assertDatabaseHas('serial_devices', [
            'id' => $device->id,
            'status' => 'active',
        ]);
    }

    public function test_updating_software_to_paid_deactivates_unlicensed_devices(): void
    {
        $device1 = SerialDevice::create([
            'serial_software_id' => $this->software->id,
            'device_id' => 'DEVICE-UNLICENSED-1',
            'status' => 'active',
        ]);

        $service = app(\App\Services\SerialSoftwareService::class);
        $service->updatePaymentSettings($this->software, [
            'requires_payment' => true,
            'price' => 15.00,
            'currency' => 'USD',
        ]);

        $this->assertDatabaseHas('serial_devices', [
            'id' => $device1->id,
            'status' => 'inactive',
        ]);
    }
}
