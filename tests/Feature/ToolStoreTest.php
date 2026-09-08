<?php

namespace Tests\Feature;

use App\Models\SerialDevice;
use App\Models\SerialSoftware;
use App\Models\SerialSoftwareLicense;
use App\Models\SerialUserDevice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ToolStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_view_tools_store(): void
    {
        $user = User::factory()->create();
        SerialSoftware::create([
            'name' => 'WAContactsExtract',
            'default_status' => 'active',
            'requires_payment' => true,
            'price' => 29.99,
            'currency' => 'USD',
        ]);

        $response = $this->actingAs($user)->get(route('store.tools.index'));
        $response->assertOk();
    }

    public function test_purchase_activates_software_license_on_email(): void
    {
        $software = SerialSoftware::create([
            'name' => 'WAContactsExtract',
            'default_status' => 'active',
            'requires_payment' => true,
            'price' => 29.99,
            'currency' => 'USD',
        ]);

        $response = $this->from(route('store.tools.index'))
            ->post(route('store.tools.purchase', $software->id), [
                'email' => 'client.tool@example.com',
                'name' => 'Test Client',
                'device_id' => 'HWID-ALPHA-999',
            ]);

        $response->assertSessionHas('success');

        $user = User::where('email', 'client.tool@example.com')->first();
        $this->assertNotNull($user);

        // Assert license exists and is active
        $license = SerialSoftwareLicense::where('user_id', $user->id)
            ->where('serial_software_id', $software->id)
            ->first();
        $this->assertNotNull($license);
        $this->assertEquals(SerialSoftwareLicense::STATUS_ACTIVE, $license->status);
        $this->assertTrue($license->isActive());

        // Assert device record is active
        $userDevice = SerialUserDevice::where('device_id', 'HWID-ALPHA-999')->first();
        $this->assertNotNull($userDevice);
        $this->assertEquals($user->id, $userDevice->user_id);
        $this->assertEquals(SerialUserDevice::STATUS_ACTIVE, $userDevice->status);

        $serialDevice = SerialDevice::where('device_id', 'HWID-ALPHA-999')->first();
        $this->assertNotNull($serialDevice);
        $this->assertEquals(SerialDevice::STATUS_ACTIVE, $serialDevice->status);
    }

    public function test_desktop_software_link_user_auto_activates_device_with_license(): void
    {
        $user = User::factory()->create(['email' => 'licensed.user@example.com']);

        $software = SerialSoftware::create([
            'name' => 'WAContactsExtract',
            'default_status' => 'active',
            'requires_payment' => true,
            'price' => 50,
            'currency' => 'USD',
        ]);

        // User bought license on website
        SerialSoftwareLicense::create([
            'user_id' => $user->id,
            'serial_software_id' => $software->id,
            'status' => SerialSoftwareLicense::STATUS_ACTIVE,
            'expires_at' => now()->addMonths(6),
        ]);

        // User launches C# desktop app on a new PC and inputs email
        $res = $this->postJson('/api/serial/device/link-user', [
            'program_name' => 'WAContactsExtract',
            'device_id' => 'NEW-MACHINE-ID-777',
            'email' => 'licensed.user@example.com',
        ]);

        $res->assertOk();
        $res->assertJson([
            'status' => 'active',
            'user_exists' => true,
            'has_active_license' => true,
        ]);

        // Verify device in DB is activated
        $userDevice = SerialUserDevice::where('device_id', 'NEW-MACHINE-ID-777')->first();
        $this->assertNotNull($userDevice);
        $this->assertEquals('active', $userDevice->status);
    }

    public function test_can_view_my_licenses(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('store.tools.my-licenses'));
        $response->assertOk();
    }
}
