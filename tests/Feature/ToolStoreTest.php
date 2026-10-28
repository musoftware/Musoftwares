<?php

namespace Tests\Feature;

use App\Models\SerialDevice;
use App\Models\SerialSoftware;
use App\Models\SerialSoftwareLicense;
use App\Models\SerialUserDevice;
use App\Models\StoreTool;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ToolStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_old_store_links_go_to_the_software_store(): void
    {
        $this->get('/store/tools')->assertRedirect('/services');

        $admin = User::factory()->create(['onboarding_completed' => true]);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->assignRole('admin');
        $this->actingAs($admin)->get('/admin/store-tools')->assertRedirect('/admin/software-products');
    }

    public function test_a_published_tool_missing_from_the_store_is_copied_there_hidden(): void
    {
        $software = SerialSoftware::create(['name' => 'Old Tool', 'requires_payment' => true, 'default_status' => 'inactive']);
        StoreTool::create(['name' => 'Old Tool', 'slug' => 'old-tool', 'price' => 300, 'currency' => 'EGP', 'serial_software_id' => $software->id, 'is_published' => true]);
        StoreTool::create(['name' => 'Draft Tool', 'slug' => 'draft-tool', 'price' => 10, 'currency' => 'EGP', 'is_published' => false]);

        $migration = require base_path('Modules/SoftwareStore/Database/Migrations/2026_10_28_130000_move_store_tools_into_software_products.php');
        $migration->up();
        $migration->up(); // running twice copies once

        $product = \Modules\SoftwareStore\Models\SoftwareProduct::where('serial_software_id', $software->id)->sole();
        $this->assertSame('old-tool', $product->slug);
        $this->assertFalse($product->is_active);
        $this->assertEquals(300, (float) $product->price);
        $this->assertFalse(\Modules\SoftwareStore\Models\SoftwareProduct::where('name_en', 'Draft Tool')->exists());
    }

    public function test_desktop_software_link_user_auto_activates_device_with_license(): void
    {
        $user = User::factory()->create(['email' => 'licensed.user@example.com']);

        $software = SerialSoftware::create([
            'name'           => 'WAContactsExtract',
            'default_status' => 'active',
            'requires_payment' => true,
            'price'          => 50,
            'currency'       => 'USD',
        ]);

        // User bought license on website
        SerialSoftwareLicense::create([
            'user_id'            => $user->id,
            'serial_software_id' => $software->id,
            'status'             => SerialSoftwareLicense::STATUS_ACTIVE,
            'expires_at'         => now()->addMonths(6),
        ]);

        // User launches C# desktop app on a new PC and inputs email
        $res = $this->postJson('/api/serial/device/link-user', [
            'program_name' => 'WAContactsExtract',
            'device_id'    => 'NEW-MACHINE-ID-777',
            'email'        => 'licensed.user@example.com',
        ]);

        $res->assertOk();
        $res->assertJson([
            'status'             => 'active',
            'user_exists'        => true,
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
