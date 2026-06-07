<?php

namespace Tests\Feature;

use App\Models\SerialDevice;
use App\Models\SerialSoftware;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SerialDeviceControllerTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->admin = User::factory()->create([
            'onboarding_completed' => true,
        ]);
        $this->admin->assignRole('admin');
    }

    /* ─── Index ───────────────────────────────────────────────────── */

    public function test_index_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.serial-devices.index'));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/SerialDevices/Index')
            ->has('devices')
            ->has('filters')
            ->has('stats')
            ->has('softwares')
            ->has('osVersions')
            ->has('perPageOptions')
        );
    }

    public function test_index_stats_are_correct(): void
    {
        $software = SerialSoftware::factory()->create();
        SerialDevice::factory()->count(3)->for($software, 'software')->create(['status' => 'active']);
        SerialDevice::factory()->count(2)->for($software, 'software')->create(['status' => 'inactive']);
        SerialDevice::factory()->for($software, 'software')->create(['status' => 'blocked']);

        $response = $this->actingAs($this->admin)->get(route('admin.serial-devices.index'));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('stats.total', 6)
            ->where('stats.active', 3)
            ->where('stats.inactive', 2)
            ->where('stats.blocked', 1)
        );
    }

    public function test_index_filters_by_status(): void
    {
        $software = SerialSoftware::factory()->create();
        SerialDevice::factory()->count(3)->for($software, 'software')->create(['status' => 'active']);
        SerialDevice::factory()->count(2)->for($software, 'software')->create(['status' => 'blocked']);

        $response = $this->actingAs($this->admin)->get(route('admin.serial-devices.index', ['status' => 'blocked']));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('devices.data', 2)
        );
    }

    public function test_index_filters_by_search(): void
    {
        $software = SerialSoftware::factory()->create();
        SerialDevice::factory()->for($software, 'software')->create([
            'device_id' => 'unique-search-device-id',
            'machine_name' => 'FindThisMachine',
        ]);
        SerialDevice::factory()->count(3)->for($software, 'software')->create();

        $response = $this->actingAs($this->admin)->get(route('admin.serial-devices.index', ['search' => 'FindThisMachine']));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('devices.data', 1)
        );
    }

    public function test_index_filters_by_software(): void
    {
        $software1 = SerialSoftware::factory()->create();
        $software2 = SerialSoftware::factory()->create();
        SerialDevice::factory()->count(2)->for($software1, 'software')->create();
        SerialDevice::factory()->count(3)->for($software2, 'software')->create();

        $response = $this->actingAs($this->admin)->get(route('admin.serial-devices.index', ['software_id' => $software1->id]));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('devices.data', 2)
        );
    }

    public function test_index_sorts_by_column(): void
    {
        $software = SerialSoftware::factory()->create();
        SerialDevice::factory()->for($software, 'software')->create(['machine_name' => 'AAA-Machine']);
        SerialDevice::factory()->for($software, 'software')->create(['machine_name' => 'ZZZ-Machine']);

        $response = $this->actingAs($this->admin)->get(route('admin.serial-devices.index', ['sort_by' => 'machine_name', 'direction' => 'asc']));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('devices.data.0.machine_name', 'AAA-Machine')
        );
    }

    public function test_index_respects_per_page(): void
    {
        $software = SerialSoftware::factory()->create();
        SerialDevice::factory()->count(15)->for($software, 'software')->create();

        $response = $this->actingAs($this->admin)->get(route('admin.serial-devices.index', ['per_page' => 10]));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('devices.data', 10)
        );
    }

    /* ─── Update Status ──────────────────────────────────────────── */

    public function test_update_status_changes_device_status(): void
    {
        $software = SerialSoftware::factory()->create();
        $device = SerialDevice::factory()->for($software, 'software')->create(['status' => 'active']);

        $response = $this->actingAs($this->admin)->patch(
            route('admin.serial-devices.status', $device),
            ['status' => 'blocked']
        );

        $response->assertRedirect();
        $this->assertDatabaseHas('serial_devices', ['id' => $device->id, 'status' => 'blocked']);
    }

    /* ─── Destroy ────────────────────────────────────────────────── */

    public function test_destroy_deletes_device(): void
    {
        $software = SerialSoftware::factory()->create();
        $device = SerialDevice::factory()->for($software, 'software')->create();

        $response = $this->actingAs($this->admin)->delete(route('admin.serial-devices.destroy', $device));

        $response->assertRedirect();
        $this->assertDatabaseMissing('serial_devices', ['id' => $device->id]);
    }

    /* ─── Export ──────────────────────────────────────────────────── */

    public function test_export_returns_csv(): void
    {
        $software = SerialSoftware::factory()->create();
        SerialDevice::factory()->count(3)->for($software, 'software')->create();

        $response = $this->actingAs($this->admin)->get(route('admin.serial-devices.export'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    public function test_export_respects_status_filter(): void
    {
        $software = SerialSoftware::factory()->create();
        SerialDevice::factory()->count(3)->for($software, 'software')->create(['status' => 'active']);
        SerialDevice::factory()->count(2)->for($software, 'software')->create(['status' => 'blocked']);

        $response = $this->actingAs($this->admin)->get(route('admin.serial-devices.export', ['status' => 'active']));

        $response->assertOk();
        // Count data rows (header + 3 active devices = 4 lines, excluding trailing newline)
        $lines = array_filter(explode("\n", $response->streamedContent()), fn($l) => trim($l) !== '');
        $this->assertCount(4, $lines); // header + 3 data rows
    }

    /* ─── Bulk Actions ───────────────────────────────────────────── */

    public function test_bulk_update_status(): void
    {
        $software = SerialSoftware::factory()->create();
        $devices = SerialDevice::factory()->count(3)->for($software, 'software')->create(['status' => 'active']);

        $response = $this->actingAs($this->admin)->post(
            route('admin.serial-devices.bulk-status'),
            ['ids' => $devices->pluck('id')->all(), 'status' => 'blocked']
        );

        $response->assertRedirect();
        foreach ($devices as $device) {
            $this->assertDatabaseHas('serial_devices', ['id' => $device->id, 'status' => 'blocked']);
        }
    }

    public function test_bulk_delete(): void
    {
        $software = SerialSoftware::factory()->create();
        $devices = SerialDevice::factory()->count(3)->for($software, 'software')->create();

        $response = $this->actingAs($this->admin)->post(
            route('admin.serial-devices.bulk-delete'),
            ['ids' => $devices->pluck('id')->all()]
        );

        $response->assertRedirect();
        foreach ($devices as $device) {
            $this->assertDatabaseMissing('serial_devices', ['id' => $device->id]);
        }
    }

    /* ─── Auth Guards ────────────────────────────────────────────── */

    public function test_guest_is_redirected(): void
    {
        $response = $this->get(route('admin.serial-devices.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_non_admin_is_forbidden(): void
    {
        $user = User::factory()->create([
            'onboarding_completed' => true,
        ]);
        $user->assignRole('client');
        $response = $this->actingAs($user)->get(route('admin.serial-devices.index'));
        $response->assertForbidden();
    }
}
