<?php

namespace Tests\Feature;

use App\Models\SerialSoftware;
use App\Models\StoreTool;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminStoreToolTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $client;

    protected function setUp(): void
    {
        parent::setUp();

        app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create([
            'email_verified_at'    => now(),
            'onboarding_completed' => true,
        ]);
        $this->admin->assignRole('admin');

        $this->client = User::factory()->create([
            'email_verified_at'    => now(),
            'onboarding_completed' => true,
        ]);
        $this->client->assignRole('client');
    }

    public function test_admin_can_view_store_tools_index(): void
    {
        StoreTool::create([
            'name'             => 'Test Auto Extractor',
            'tagline'          => 'Fast extraction tool',
            'price'            => 19.99,
            'currency'         => 'USD',
            'requires_payment' => true,
            'is_published'     => true,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.store-tools.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/StoreTools/Index')
            ->has('tools.data', 1)
            ->where('tools.data.0.name', 'Test Auto Extractor')
        );
    }

    public function test_admin_can_create_store_tool_linked_to_serial_software(): void
    {
        $software = SerialSoftware::create([
            'name'           => 'WAMessengerPro',
            'default_status' => 'active',
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.store-tools.store'), [
            'name'                 => 'WA Messenger Pro Tool',
            'tagline'              => 'The ultimate sender',
            'description'          => 'Automated multi-account bulk sender.',
            'version'              => '2.1.0',
            'download_url'         => 'https://example.com/download.exe',
            'category'             => 'Marketing',
            'price'                => 49.00,
            'currency'             => 'USD',
            'requires_payment'     => true,
            'whatsapp_number'      => '+201000000000',
            'payment_instructions' => 'Send payment via Vodafone Cash',
            'features'             => ['Feature A', 'Feature B'],
            'serial_software_id'   => $software->id,
            'is_published'         => true,
            'sort_order'           => 1,
        ]);

        $response->assertSessionHas('success');

        $tool = StoreTool::where('name', 'WA Messenger Pro Tool')->first();
        $this->assertNotNull($tool);
        $this->assertEquals($software->id, $tool->serial_software_id);
        $this->assertTrue($tool->is_published);
        $this->assertTrue($tool->requires_payment);
        $this->assertEquals(49.00, (float) $tool->price);
        $this->assertEquals(['Feature A', 'Feature B'], $tool->features);
    }

    public function test_admin_can_update_store_tool(): void
    {
        $tool = StoreTool::create([
            'name'         => 'Old Name',
            'price'        => 10.00,
            'currency'     => 'USD',
            'is_published' => true,
        ]);

        $response = $this->actingAs($this->admin)->put(route('admin.store-tools.update', $tool->id), [
            'name'             => 'Updated Name',
            'tagline'          => 'New tagline',
            'price'            => 25.00,
            'currency'         => 'USD',
            'requires_payment' => true,
            'is_published'     => false,
        ]);

        $response->assertSessionHas('success');
        $tool->refresh();

        $this->assertEquals('Updated Name', $tool->name);
        $this->assertEquals(25.00, (float) $tool->price);
        $this->assertFalse($tool->is_published);
    }

    public function test_admin_can_toggle_published_status(): void
    {
        $tool = StoreTool::create([
            'name'         => 'Toggle Tool',
            'is_published' => true,
        ]);

        $response = $this->actingAs($this->admin)->patch(route('admin.store-tools.toggle-status', $tool->id));
        $response->assertSessionHas('success');

        $tool->refresh();
        $this->assertFalse($tool->is_published);

        $this->actingAs($this->admin)->patch(route('admin.store-tools.toggle-status', $tool->id));
        $tool->refresh();
        $this->assertTrue($tool->is_published);
    }

    public function test_admin_can_soft_delete_store_tool(): void
    {
        $tool = StoreTool::create([
            'name'         => 'To Delete',
            'is_published' => true,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.store-tools.destroy', $tool->id));
        $response->assertSessionHas('success');

        $this->assertSoftDeleted('store_tools', ['id' => $tool->id]);
    }

    public function test_non_admin_cannot_manage_store_tools(): void
    {
        $response = $this->actingAs($this->client)->get(route('admin.store-tools.index'));
        $response->assertForbidden();

        $responsePost = $this->actingAs($this->client)->post(route('admin.store-tools.store'), [
            'name' => 'Hacker Tool',
        ]);
        $responsePost->assertForbidden();
    }
}
