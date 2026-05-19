<?php

use App\Models\User;
use Illuminate\Support\Str;
use Modules\Tools\Models\ActivatedDevice;
use Modules\Tools\Models\Tool;
use Modules\Tools\Models\ToolLicense;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->tool = Tool::factory()->create(['slug' => 'test-tool']);
    
    // Create a valid license for the user
    $this->license = ToolLicense::factory()->create([
        'user_id' => $this->user->id,
        'tool_id' => $this->tool->id,
        'license_key' => Str::uuid(),
        'max_devices' => 2,
        'status' => 'active',
        'expires_at' => now()->addDays(30),
    ]);
});

it('can activate a new device', function () {
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
                 'max_devices' => 2,
             ]);

    $this->assertDatabaseHas('tools_activated_devices', [
        'tool_license_id' => $this->license->id,
        'hardware_fingerprint' => $fingerprint,
        'status' => 'active',
    ]);
});

it('prevents activation if license is expired', function () {
    $this->license->update(['status' => 'expired']);

    $response = $this->actingAs($this->user)->postJson('/api/tools/license/activate', [
        'license_key' => $this->license->license_key,
        'hardware_fingerprint' => 'hw-123',
    ]);

    $response->assertStatus(403)
             ->assertJson(['success' => false, 'error' => 'license_expired_or_revoked']);
});

it('prevents activation if device limit reached', function () {
    // Fill up device limit
    ActivatedDevice::factory()->count(2)->create([
        'tool_license_id' => $this->license->id,
        'status' => 'active',
    ]);

    $response = $this->actingAs($this->user)->postJson('/api/tools/license/activate', [
        'license_key' => $this->license->license_key,
        'hardware_fingerprint' => 'new-hw-456',
    ]);

    $response->assertStatus(403)
             ->assertJson(['success' => false, 'error' => 'device_limit_reached']);
});

it('allows check for active device', function () {
    $device = ActivatedDevice::factory()->create([
        'tool_license_id' => $this->license->id,
        'hardware_fingerprint' => 'hw-active',
        'status' => 'active',
    ]);

    $response = $this->actingAs($this->user)->postJson('/api/tools/license/check', [
        'license_key' => $this->license->license_key,
        'hardware_fingerprint' => 'hw-active',
    ]);

    $response->assertStatus(200)
             ->assertJson(['valid' => true]);
});

it('fails check for unknown device', function () {
    $response = $this->actingAs($this->user)->postJson('/api/tools/license/check', [
        'license_key' => $this->license->license_key,
        'hardware_fingerprint' => 'hw-unknown',
    ]);

    $response->assertStatus(403)
             ->assertJson(['valid' => false, 'error' => 'device_not_activated']);
});

it('updates heartbeat for active device', function () {
    $device = ActivatedDevice::factory()->create([
        'tool_license_id' => $this->license->id,
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

    $this->assertDatabaseHas('tools_activated_devices', [
        'id' => $device->id,
        'app_version' => '1.0.1',
    ]);
});
