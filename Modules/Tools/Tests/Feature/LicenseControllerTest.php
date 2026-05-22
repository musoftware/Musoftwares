<?php

namespace Modules\Tools\Tests\Feature;

use App\Models\User;
use Illuminate\Support\Str;
use Modules\Tools\Models\ActivatedDevice;
use Modules\Tools\Models\ToolLicense;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LicenseControllerTest extends TestCase
{
    protected User $user;
    protected ToolLicense $license;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate:fresh', [
            '--path' => [
                'Modules/Tools/Database/Migrations',
                'database/migrations',
                'Modules/Core/Database/Migrations',
                'Modules/ERP/Database/Migrations',
            ]
        ]);

        $this->user = User::factory()->create();
        
        // Create a valid license for the user
        $this->license = ToolLicense::create([
            'user_id' => $this->user->id,
            'tool_guid' => 'whatsapp-sender-pro',
            'license_key' => (string) Str::uuid(),
            'status' => 'active',
            'expires_at' => now()->addDays(30),
        ]);
    }

    public function test_can_activate_a_new_device()
    {
        $fingerprint = 'hw-fingerprint-123';
        
        $response = $this->actingAs($this->user)->postJson('/api/tools/license/activate', [
            'license_key' => $this->license->license_key,
            'hardware_fingerprint' => $fingerprint,
            'device_name' => 'Test PC',
            'os' => 'windows',
            'app_version' => '1.0.0',
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ]);

        $this->assertDatabaseHas('activated_devices', [
            'tool_license_id' => $this->license->id,
            'hardware_fingerprint' => $fingerprint,
            'status' => 'active',
        ]);
    }

    public function test_prevents_activation_if_license_is_expired()
    {
        $this->license->update(['status' => 'expired']);

        $response = $this->actingAs($this->user)->postJson('/api/tools/license/activate', [
            'license_key' => $this->license->license_key,
            'hardware_fingerprint' => 'hw-123',
        ]);

        $response->assertStatus(403)
                 ->assertJson(['success' => false, 'error' => 'license_expired_or_revoked']);
    }

    public function test_allows_check_for_active_device()
    {
        $device = ActivatedDevice::create([
            'tool_license_id' => $this->license->id,
            'user_id' => $this->user->id,
            'hardware_fingerprint' => 'hw-active',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/tools/license/check', [
            'license_key' => $this->license->license_key,
            'hardware_fingerprint' => 'hw-active',
        ]);

        $response->assertStatus(200)
                 ->assertJson(['valid' => true]);
    }

    public function test_fails_check_for_unknown_device()
    {
        $response = $this->actingAs($this->user)->postJson('/api/tools/license/check', [
            'license_key' => $this->license->license_key,
            'hardware_fingerprint' => 'hw-unknown',
        ]);

        $response->assertStatus(403)
                 ->assertJson(['valid' => false, 'error' => 'device_not_activated']);
    }

    public function test_updates_heartbeat_for_active_device()
    {
        $device = ActivatedDevice::create([
            'tool_license_id' => $this->license->id,
            'user_id' => $this->user->id,
            'hardware_fingerprint' => 'hw-active',
            'status' => 'active',
            'last_seen_at' => now()->subHours(2),
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/tools/license/heartbeat', [
            'license_key' => $this->license->license_key,
            'hardware_fingerprint' => 'hw-active',
            'app_version' => '1.0.1',
        ]);

        $response->assertStatus(200)
                 ->assertJson(['alive' => true]);

        $this->assertDatabaseHas('activated_devices', [
            'id' => $device->id,
            'app_version' => '1.0.1',
        ]);
    }
}
