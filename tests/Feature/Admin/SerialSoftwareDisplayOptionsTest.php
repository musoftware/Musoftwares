<?php

namespace Tests\Feature\Admin;

use App\Models\SerialDevice;
use App\Models\SerialSoftware;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SerialSoftwareDisplayOptionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_software_defaults_to_show_price_and_show_whatsapp_true(): void
    {
        $software = SerialSoftware::create([
            'name' => 'WhatsApp Bulker Test',
            'is_active' => true,
        ]);

        $this->assertTrue((bool) ($software->show_price ?? true));
        $this->assertTrue((bool) ($software->show_whatsapp ?? true));
    }

    public function test_admin_can_update_display_options_via_payment_endpoint(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create([
            'onboarding_completed' => true,
        ]);
        $admin->assignRole('admin');

        $software = SerialSoftware::create([
            'name' => 'Test Bulker',
            'is_active' => true,
            'requires_payment' => true,
            'price' => 50,
            'show_price' => true,
            'show_whatsapp' => true,
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.serial-softwares.payment', $software->id), [
            'requires_payment' => true,
            'price' => 50,
            'currency' => 'USD',
            'whatsapp_number' => '+201012345678',
            'payment_instructions' => 'Please contact us',
            'show_price' => false,
            'show_whatsapp' => false,
        ]);

        $response->assertSessionHasNoErrors();
        $software->refresh();

        $this->assertFalse((bool) $software->show_price);
        $this->assertFalse((bool) $software->show_whatsapp);
    }

    public function test_admin_can_update_display_options_via_full_settings_page(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create([
            'onboarding_completed' => true,
        ]);
        $admin->assignRole('admin');

        $software = SerialSoftware::create([
            'name' => 'Full Settings Bulker',
            'is_active' => true,
            'pricing_type' => 'single',
            'price' => 100,
            'show_price' => true,
            'show_whatsapp' => true,
        ]);

        $response = $this->actingAs($admin)->put(route('admin.serial-softwares.settings.update', $software->id), [
            'name' => 'Full Settings Bulker Updated',
            'is_active' => true,
            'default_status' => 'active',
            'pricing_type' => 'single',
            'price' => 120,
            'reseller_price' => 80,
            'currency' => 'USD',
            'billing_cycle' => 'lifetime',
            'whatsapp_number' => '+201099999999',
            'payment_instructions' => 'Updated instructions',
            'show_price' => false,
            'show_whatsapp' => false,
        ]);

        $response->assertSessionHasNoErrors();
        $software->refresh();

        $this->assertFalse((bool) $software->show_price);
        $this->assertFalse((bool) $software->show_whatsapp);
        $this->assertEquals(120, $software->price);
    }

    public function test_serial_device_api_returns_show_price_and_show_whatsapp_flags(): void
    {
        $software = SerialSoftware::create([
            'name' => 'WhatsApp Bulker',
            'is_active' => true,
            'requires_payment' => true,
            'price' => 99.00,
            'currency' => 'USD',
            'whatsapp_number' => '+201000000000',
            'show_price' => false,
            'show_whatsapp' => true,
        ]);

        $response = $this->postJson('/api/serial/device', [
            'program_name' => 'WhatsApp Bulker',
            'device_id' => 'DEVICE-TEST-XYZ-123',
        ]);

        $response->assertOk();
        $response->assertJson([
            'requires_payment' => true,
            'show_price' => false,
            'show_whatsapp' => true,
            'price' => 99.00,
            'whatsapp_number' => '+201000000000',
        ]);
    }
}
