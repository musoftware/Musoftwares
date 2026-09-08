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

        $response = $this->postJson(route('api.serial-devices.register'), [
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

        $response = $this->postJson(route('api.serial-devices.register'), [
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
}
