<?php

namespace Tests\Feature\Portal;

use App\Models\SerialDevice;
use App\Models\SerialSoftware;
use App\Models\SerialSoftwareReseller;
use App\Models\SerialUserDevice;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResellerDeviceControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $reseller;

    protected User $regularUser;

    protected SerialSoftware $software;

    protected SerialSoftwareReseller $allocation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        // Reseller user
        $this->reseller = User::factory()->create([
            'onboarding_completed' => true,
        ]);
        $this->reseller->assignRole('software_reseller');

        // Regular non-reseller user
        $this->regularUser = User::factory()->create([
            'onboarding_completed' => true,
        ]);
        $this->regularUser->assignRole('client');

        // Create software and allocate to reseller with 5 devices quota
        $this->software = SerialSoftware::create([
            'name' => 'Dental Pro System',
            'default_status' => 'active',
        ]);

        $this->allocation = SerialSoftwareReseller::create([
            'user_id' => $this->reseller->id,
            'serial_software_id' => $this->software->id,
            'max_devices' => 5,
            'status' => 'active',
        ]);
    }

    public function test_reseller_can_view_devices_portal()
    {
        $response = $this->actingAs($this->reseller)->get('/portal/devices');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Portal/Devices/Index')
            ->has('allocatedSoftwares', 1)
            ->has('stats')
            ->has('devices.data')
        );
    }

    public function test_non_reseller_cannot_view_devices_portal()
    {
        $response = $this->actingAs($this->regularUser)->get('/portal/devices');
        $response->assertStatus(403);
    }

    public function test_reseller_can_assign_device_for_one_month()
    {
        $response = $this->actingAs($this->reseller)->post('/portal/devices', [
            'serial_software_id' => $this->software->id,
            'device_id' => 'HWID-TEST-001',
            'customer_name' => 'Dr. Ahmed',
            'customer_email' => 'ahmed@example.com',
            'customer_phone' => '01012345678',
            'duration_preset' => '1_month',
            'notes' => 'Branch Clinic 1',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        // Customer created
        $customer = User::where('email', 'ahmed@example.com')->first();
        $this->assertNotNull($customer);

        // Device assignment created
        $assignment = SerialUserDevice::where('device_id', 'HWID-TEST-001')->first();
        $this->assertNotNull($assignment);
        $this->assertEquals($this->reseller->id, $assignment->reseller_id);
        $this->assertEquals($customer->id, $assignment->user_id);
        $this->assertEquals('active', $assignment->status);
        $this->assertNotNull($assignment->expires_at);

        // Expiry should be approx 1 month in the future
        $this->assertTrue($assignment->expires_at->greaterThan(now()->addDays(25)));
        $this->assertTrue($assignment->expires_at->lessThan(now()->addDays(35)));
        $this->assertFalse($assignment->isExpired());
    }

    public function test_reseller_cannot_exceed_device_quota()
    {
        // Allocate quota of 1 device
        $this->allocation->update(['max_devices' => 1]);

        // Assign 1st device
        $this->actingAs($this->reseller)->post('/portal/devices', [
            'serial_software_id' => $this->software->id,
            'device_id' => 'HWID-1',
            'customer_name' => 'User 1',
            'customer_email' => 'u1@example.com',
            'duration_preset' => '1_month',
        ]);

        // Try to assign 2nd device
        $response = $this->actingAs($this->reseller)->post('/portal/devices', [
            'serial_software_id' => $this->software->id,
            'device_id' => 'HWID-2',
            'customer_name' => 'User 2',
            'customer_email' => 'u2@example.com',
            'duration_preset' => '1_month',
        ]);

        $response->assertSessionHasErrors('serial_software_id');
    }

    public function test_reseller_can_renew_device_by_one_month()
    {
        $customer = User::factory()->create();

        $assignment = SerialUserDevice::create([
            'user_id' => $customer->id,
            'reseller_id' => $this->reseller->id,
            'device_id' => 'HWID-RENEW-01',
            'status' => 'active',
            'expires_at' => now()->addDays(5), // expires in 5 days
        ]);

        $oldExpiry = $assignment->expires_at;

        $response = $this->actingAs($this->reseller)->post("/portal/devices/{$assignment->id}/renew", [
            'duration_preset' => '1_month',
        ]);

        $response->assertSessionHasNoErrors();
        $assignment->refresh();

        // New expiry should be approximately oldExpiry + 1 month
        $this->assertTrue($assignment->expires_at->greaterThan($oldExpiry));
        $this->assertTrue($assignment->expires_at->greaterThan(now()->addDays(30)));
    }

    public function test_reseller_can_toggle_device_status()
    {
        $customer = User::factory()->create();

        $assignment = SerialUserDevice::create([
            'user_id' => $customer->id,
            'reseller_id' => $this->reseller->id,
            'device_id' => 'HWID-TOGGLE-01',
            'status' => 'active',
            'expires_at' => now()->addMonth(),
        ]);

        $response = $this->actingAs($this->reseller)->patch("/portal/devices/{$assignment->id}/status", [
            'status' => 'inactive',
        ]);

        $response->assertSessionHasNoErrors();
        $assignment->refresh();
        $this->assertEquals('inactive', $assignment->status);
    }
}
