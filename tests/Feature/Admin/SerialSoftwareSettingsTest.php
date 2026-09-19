<?php

namespace Tests\Feature\Admin;

use App\Models\SerialDevice;
use App\Models\SerialSoftware;
use App\Models\SerialSoftwareKey;
use App\Models\SerialSoftwarePackage;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SerialSoftwareSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create([
            'onboarding_completed' => true,
        ]);
        $this->admin->assignRole('admin');
    }

    public function test_settings_page_renders_successfully(): void
    {
        $software = SerialSoftware::factory()->create([
            'name' => 'WhatsApp Bulker',
            'is_active' => true,
            'pricing_type' => 'packages',
        ]);

        SerialSoftwareKey::create([
            'serial_software_id' => $software->id,
            'key' => 'max_accounts',
            'default_value' => '1',
        ]);

        SerialSoftwarePackage::create([
            'serial_software_id' => $software->id,
            'name' => 'Pro Monthly',
            'price' => 29.99,
            'currency' => 'USD',
            'billing_cycle' => 'monthly',
            'custom_values' => ['max_accounts' => '5'],
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.serial-softwares.settings', $software->id));

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/SerialSoftwares/Settings')
            ->where('software.name', 'WhatsApp Bulker')
            ->where('software.is_active', true)
            ->where('software.pricing_type', 'packages')
            ->has('software.custom_keys', 1)
            ->has('software.packages', 1)
        );
    }

    public function test_update_settings_saves_general_and_single_pricing(): void
    {
        $software = SerialSoftware::factory()->create([
            'name' => 'Old Name',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->put(route('admin.serial-softwares.settings.update', $software->id), [
            'name' => 'New Software Name',
            'is_active' => false,
            'default_status' => 'inactive',
            'pricing_type' => 'single',
            'price' => 49.99,
            'currency' => 'USD',
            'billing_cycle' => 'annual',
            'billing_days' => null,
            'whatsapp_number' => '+201015218548',
            'payment_instructions' => 'Send via Vodafone Cash',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('serial_softwares', [
            'id' => $software->id,
            'name' => 'New Software Name',
            'is_active' => false,
            'default_status' => 'inactive',
            'pricing_type' => 'single',
            'requires_payment' => true,
            'price' => 49.99,
            'billing_cycle' => 'annual',
            'whatsapp_number' => '+201015218548',
        ]);
    }

    public function test_update_settings_with_free_pricing_and_null_billing_cycle(): void
    {
        $software = SerialSoftware::factory()->create([
            'name' => 'AudFinder',
            'is_active' => true,
            'pricing_type' => 'free',
            'billing_cycle' => 'lifetime',
        ]);

        $response = $this->actingAs($this->admin)->put(route('admin.serial-softwares.settings.update', $software->id), [
            'name' => 'AudFinder Updated',
            'is_active' => true,
            'default_status' => 'active',
            'pricing_type' => 'free',
            'price' => null,
            'reseller_price' => null,
            'currency' => null,
            'billing_cycle' => null,
            'billing_days' => null,
            'whatsapp_number' => null,
            'payment_instructions' => null,
            'show_price' => true,
            'show_whatsapp' => true,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('serial_softwares', [
            'id' => $software->id,
            'name' => 'AudFinder Updated',
            'pricing_type' => 'free',
            'requires_payment' => false,
            'billing_cycle' => 'lifetime',
            'currency' => 'USD',
        ]);
    }

    public function test_logo_upload_saves_to_public_and_logo_url_uses_public_not_storage(): void
    {
        $software = SerialSoftware::factory()->create([
            'name' => 'Logo Test App',
        ]);

        $file = \Illuminate\Http\UploadedFile::fake()->image('app-logo.png', 200, 200);

        $response = $this->actingAs($this->admin)->put(route('admin.serial-softwares.settings.update', $software->id), [
            'name' => 'Logo Test App',
            'is_active' => true,
            'default_status' => 'active',
            'pricing_type' => 'free',
            'logo' => $file,
        ]);

        $response->assertRedirect();
        $software->refresh();

        $this->assertNotNull($software->logo_path);
        $this->assertStringStartsWith('serial-software-logos/', $software->logo_path);
        $this->assertFileExists(public_path($software->logo_path));

        // logo_url should serve directly from public, NOT containing /storage/
        $this->assertStringNotContainsString('/storage/', $software->logo_url);
        $this->assertStringContainsString('serial-software-logos/', $software->logo_url);

        // Cleanup created test file
        if (file_exists(public_path($software->logo_path))) {
            @unlink(public_path($software->logo_path));
        }
    }

    public function test_package_crud_lifecycle(): void
    {
        $software = SerialSoftware::factory()->create([
            'name' => 'Test Bulker',
            'pricing_type' => 'packages',
        ]);

        // Create package
        $response = $this->actingAs($this->admin)->post(route('admin.serial-softwares.packages.store', $software->id), [
            'name' => 'Starter Pack',
            'price' => 15.00,
            'currency' => 'USD',
            'billing_cycle' => 'monthly',
            'is_active' => true,
            'is_default' => true,
            'sort_order' => 1,
            'custom_values' => ['accounts' => '2'],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('serial_software_packages', [
            'serial_software_id' => $software->id,
            'name' => 'Starter Pack',
            'price' => 15.00,
            'is_default' => true,
        ]);

        $package = SerialSoftwarePackage::where('name', 'Starter Pack')->firstOrFail();

        // Update package
        $updateResponse = $this->actingAs($this->admin)->put(
            route('admin.serial-softwares.packages.update', [$software->id, $package->id]),
            [
                'name' => 'Starter Pack V2',
                'price' => 19.99,
                'currency' => 'USD',
                'billing_cycle' => 'monthly',
                'is_active' => true,
                'is_default' => true,
                'sort_order' => 1,
                'custom_values' => ['accounts' => '3'],
            ]
        );

        $updateResponse->assertRedirect();
        $this->assertDatabaseHas('serial_software_packages', [
            'id' => $package->id,
            'name' => 'Starter Pack V2',
            'price' => 19.99,
        ]);

        // Delete package
        $deleteResponse = $this->actingAs($this->admin)->delete(
            route('admin.serial-softwares.packages.destroy', [$software->id, $package->id])
        );

        $deleteResponse->assertRedirect();
        $this->assertSoftDeleted('serial_software_packages', ['id' => $package->id]);
    }

    public function test_api_check_in_master_kill_switch_rejects_devices(): void
    {
        $software = SerialSoftware::factory()->create([
            'name' => 'Deactivated App',
            'is_active' => false,
        ]);

        $response = $this->postJson('/api/serial/device', [
            'program_name' => 'Deactivated App',
            'device_id' => 'DEV-KILL-12345',
        ]);

        $response->assertOk();
        $response->assertJson([
            'status' => 'inactive',
            'is_active' => false,
            'software_disabled' => true,
        ]);
    }

    public function test_api_check_in_returns_packages_and_resolved_keys(): void
    {
        $software = SerialSoftware::factory()->create([
            'name' => 'Packaged App',
            'is_active' => true,
            'pricing_type' => 'packages',
            'requires_payment' => true,
        ]);

        $key = SerialSoftwareKey::create([
            'serial_software_id' => $software->id,
            'key' => 'limit_per_day',
            'default_value' => '100',
        ]);

        $package = SerialSoftwarePackage::create([
            'serial_software_id' => $software->id,
            'name' => 'Tier 1',
            'price' => 20.00,
            'currency' => 'USD',
            'billing_cycle' => 'monthly',
            'is_active' => true,
            'custom_values' => ['limit_per_day' => '500'],
        ]);

        $response = $this->postJson('/api/serial/device', [
            'program_name' => 'Packaged App',
            'device_id' => 'DEV-PKG-TEST',
        ]);

        $response->assertOk();
        $response->assertJson([
            'status' => 'inactive',
            'is_active' => true,
            'pricing_type' => 'packages',
        ]);

        $data = $response->json();
        $this->assertNotEmpty($data['packages']);
        $this->assertEquals('Tier 1', $data['packages'][0]['name']);
        $this->assertEquals(20.00, $data['packages'][0]['price']);
    }

    public function test_update_settings_saves_trial_configuration(): void
    {
        $software = SerialSoftware::factory()->create([
            'name' => 'Trial App',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->put(route('admin.serial-softwares.settings.update', $software->id), [
            'name' => 'Trial App',
            'is_active' => true,
            'default_status' => 'inactive',
            'pricing_type' => 'single',
            'price' => 25.00,
            'trial_enabled' => true,
            'trial_days' => 2,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('serial_softwares', [
            'id' => $software->id,
            'trial_enabled' => true,
            'trial_days' => 2,
        ]);
    }

    public function test_trial_does_not_activate_automatically_on_check_in(): void
    {
        $software = SerialSoftware::factory()->create([
            'name' => 'Trial App Check',
            'is_active' => true,
            'requires_payment' => true,
            'trial_enabled' => true,
            'trial_days' => 1,
            'default_status' => 'inactive',
        ]);

        // 1. Initial check-in from new device
        $response = $this->postJson('/api/serial/device', [
            'program_name' => 'Trial App Check',
            'device_id' => 'DEV-TRIAL-001',
        ]);

        $response->assertOk();
        // MUST remain inactive! Trial is NOT activated automatically.
        $response->assertJson([
            'status' => 'inactive',
            'trial_enabled' => true,
            'trial_days' => 1,
            'trial_claimed' => false,
            'can_claim_trial' => true,
        ]);

        $this->assertDatabaseHas('serial_devices', [
            'device_id' => 'DEV-TRIAL-001',
            'status' => 'inactive',
            'trial_claimed_at' => null,
        ]);
    }

    public function test_claim_trial_activates_device_and_prevents_duplicate_claims(): void
    {
        $software = SerialSoftware::factory()->create([
            'name' => 'Trial Claim App',
            'is_active' => true,
            'requires_payment' => true,
            'trial_enabled' => true,
            'trial_days' => 1,
            'default_status' => 'inactive',
        ]);

        // Claim trial via dedicated link API
        $claimResponse = $this->postJson('/api/serial/device/claim-trial', [
            'program_name' => 'Trial Claim App',
            'device_id' => 'DEV-TRIAL-CLAIM',
        ]);

        $claimResponse->assertOk();
        $claimResponse->assertJson([
            'success' => true,
            'status' => 'active',
            'trial_days' => 1,
        ]);

        $device = SerialDevice::where('device_id', 'DEV-TRIAL-CLAIM')->first();
        $this->assertNotNull($device);
        $this->assertEquals('active', $device->status);
        $this->assertNotNull($device->trial_claimed_at);

        // Next check-in should reflect active status with expiration and trial_claimed = true
        $checkInResponse = $this->postJson('/api/serial/device', [
            'program_name' => 'Trial Claim App',
            'device_id' => 'DEV-TRIAL-CLAIM',
        ]);

        $checkInResponse->assertOk();
        $checkInResponse->assertJson([
            'status' => 'active',
            'trial_claimed' => true,
            'can_claim_trial' => false,
        ]);

        // Attempting to claim trial again must be rejected (anti-abuse)
        $secondClaim = $this->postJson('/api/serial/device/claim-trial', [
            'program_name' => 'Trial Claim App',
            'device_id' => 'DEV-TRIAL-CLAIM',
        ]);

        $secondClaim->assertStatus(400);
        $secondClaim->assertJson([
            'success' => false,
            'message' => 'A free trial has already been claimed on this device.',
        ]);
    }
}
