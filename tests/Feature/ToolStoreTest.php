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

    public function test_can_view_tools_store_and_only_published_store_tools_appear(): void
    {
        $user = User::factory()->create();

        // 1. Raw Serial Software (auto-registered by desktop app check-in) -> MUST NOT appear
        SerialSoftware::create([
            'name'           => 'InternalAutoCheckedInApp',
            'default_status' => 'active',
        ]);

        // 2. Draft Store Tool -> MUST NOT appear
        StoreTool::create([
            'name'         => 'Draft Unreleased Tool',
            'price'        => 50,
            'is_published' => false,
        ]);

        // 3. Published Store Tool -> MUST appear
        $publishedTool = StoreTool::create([
            'name'             => 'WAContactsExtract',
            'tagline'          => 'Extract group members',
            'requires_payment' => true,
            'price'            => 29.99,
            'currency'         => 'USD',
            'is_published'     => true,
        ]);

        $response = $this->actingAs($user)->get(route('store.tools.index'));
        $response->assertOk();

        $response->assertInertia(fn ($page) => $page
            ->component('Store/Tools/Index')
            ->has('tools', 1)
            ->where('tools.0.name', 'WAContactsExtract')
        );
    }

    private function makeTool(bool $paid): StoreTool
    {
        $software = SerialSoftware::create([
            'name'           => 'WAContactsExtract',
            'default_status' => 'active',
        ]);

        return StoreTool::create([
            'name'               => 'WA Contacts Extractor',
            'serial_software_id' => $software->id,
            'requires_payment'   => $paid,
            'price'              => $paid ? 29.99 : 0,
            'currency'           => 'USD',
            'is_published'       => true,
        ]);
    }

    public function test_guest_cannot_purchase_tool(): void
    {
        $tool = $this->makeTool(false);

        $this->post(route('store.tools.purchase', $tool->id), ['device_id' => 'HWID-1'])
            ->assertRedirect(route('login'));

        $this->assertSame(0, SerialSoftwareLicense::count());
    }

    public function test_free_tool_activates_license_for_signed_in_user_only(): void
    {
        $user = User::factory()->create();
        $tool = $this->makeTool(false);

        $this->actingAs($user)
            ->from(route('store.tools.index'))
            ->post(route('store.tools.purchase', $tool->id), [
                'email'     => 'someone.else@example.com',
                'device_id' => 'HWID-ALPHA-999',
            ])
            ->assertSessionHas('success');

        $license = SerialSoftwareLicense::where('user_id', $user->id)->first();
        $this->assertNotNull($license);
        $this->assertTrue($license->isActive());
        $this->assertNull(User::where('email', 'someone.else@example.com')->first());

        $userDevice = SerialUserDevice::where('device_id', 'HWID-ALPHA-999')->first();
        $this->assertSame($user->id, $userDevice->user_id);
        $this->assertSame(SerialDevice::STATUS_ACTIVE, SerialDevice::where('device_id', 'HWID-ALPHA-999')->value('status'));
    }

    public function test_paid_tool_is_not_activated_without_payment(): void
    {
        $user = User::factory()->create();
        $tool = $this->makeTool(true);

        $this->actingAs($user)
            ->from(route('store.tools.index'))
            ->post(route('store.tools.purchase', $tool->id), ['device_id' => 'HWID-PAID'])
            ->assertSessionHas('error');

        $this->assertSame(0, SerialSoftwareLicense::count());
        $this->assertNull(SerialUserDevice::where('device_id', 'HWID-PAID')->first());
    }

    public function test_cannot_rebind_device_owned_by_another_user(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $tool = $this->makeTool(false);

        SerialUserDevice::create([
            'device_id' => 'HWID-OWNED',
            'user_id'   => $owner->id,
            'status'    => SerialUserDevice::STATUS_ACTIVE,
        ]);

        $this->actingAs($attacker)
            ->from(route('store.tools.index'))
            ->post(route('store.tools.purchase', $tool->id), ['device_id' => 'HWID-OWNED'])
            ->assertSessionHasErrors('device_id');

        $this->assertSame($owner->id, SerialUserDevice::where('device_id', 'HWID-OWNED')->value('user_id'));
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
