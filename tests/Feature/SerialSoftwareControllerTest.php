<?php

namespace Tests\Feature;

use App\Models\SerialDevice;
use App\Models\SerialSoftware;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SerialSoftwareControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'onboarding_completed' => true,
        ]);
        $this->admin->assignRole('admin');
    }

    // ─── INDEX ────────────────────────────────────────────────────────

    public function test_index_renders_successfully(): void
    {
        SerialSoftware::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)->get(route('admin.serial-softwares.index'));

        $response->assertSuccessful();
        $response->assertInertia(fn($page) =>
            $page->component('Admin/SerialSoftwares/Index')
                ->has('softwares.data', 3)
                ->has('filters')
                ->has('stats')
        );
    }

    public function test_index_returns_correct_stats(): void
    {
        $sw = SerialSoftware::factory()->create();
        SerialDevice::factory()->count(3)->active()->create(['serial_software_id' => $sw->id]);
        SerialDevice::factory()->count(2)->inactive()->create(['serial_software_id' => $sw->id]);
        SerialDevice::factory()->count(1)->blocked()->create(['serial_software_id' => $sw->id]);

        $response = $this->actingAs($this->admin)->get(route('admin.serial-softwares.index'));

        $response->assertInertia(fn($page) =>
            $page->where('stats.total_softwares', 1)
                ->where('stats.total_devices_all', 6)
                ->where('stats.active_devices_all', 3)
                ->where('stats.inactive_devices_all', 2)
                ->where('stats.blocked_devices_all', 1)
        );
    }

    public function test_index_search_filter(): void
    {
        SerialSoftware::factory()->create(['name' => 'MyApp.exe']);
        SerialSoftware::factory()->create(['name' => 'OtherTool.exe']);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.serial-softwares.index', ['search' => 'MyApp']));

        $response->assertInertia(fn($page) =>
            $page->has('softwares.data', 1)
                ->where('softwares.data.0.name', 'MyApp.exe')
        );
    }

    public function test_index_status_filter(): void
    {
        SerialSoftware::factory()->create(['default_status' => 'active']);
        SerialSoftware::factory()->create(['default_status' => 'inactive']);
        SerialSoftware::factory()->create(['default_status' => 'active']);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.serial-softwares.index', ['default_status' => 'inactive']));

        $response->assertInertia(fn($page) =>
            $page->has('softwares.data', 1)
                ->where('softwares.data.0.default_status', 'inactive')
        );
    }

    public function test_index_sorting_by_name_asc(): void
    {
        SerialSoftware::factory()->create(['name' => 'Zebra.exe']);
        SerialSoftware::factory()->create(['name' => 'Alpha.exe']);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.serial-softwares.index', ['sort_by' => 'name', 'direction' => 'asc']));

        $response->assertInertia(fn($page) =>
            $page->where('softwares.data.0.name', 'Alpha.exe')
                ->where('softwares.data.1.name', 'Zebra.exe')
        );
    }

    public function test_index_per_page(): void
    {
        SerialSoftware::factory()->count(15)->create();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.serial-softwares.index', ['per_page' => 10]));

        $response->assertInertia(fn($page) =>
            $page->has('softwares.data', 10)
        );
    }

    public function test_index_resource_has_device_counts(): void
    {
        $sw = SerialSoftware::factory()->create();
        SerialDevice::factory()->count(2)->active()->create(['serial_software_id' => $sw->id]);
        SerialDevice::factory()->count(1)->blocked()->create(['serial_software_id' => $sw->id]);

        $response = $this->actingAs($this->admin)->get(route('admin.serial-softwares.index'));

        $response->assertInertia(fn($page) =>
            $page->where('softwares.data.0.total_devices', 3)
                ->where('softwares.data.0.active_count', 2)
                ->where('softwares.data.0.blocked_count', 1)
                ->where('softwares.data.0.inactive_count', 0)
        );
    }

    // ─── STORE ────────────────────────────────────────────────────────

    public function test_store_creates_software(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.serial-softwares.store'), [
                'name' => 'NewApp.exe',
                'default_status' => 'active',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('serial_softwares', [
            'name' => 'NewApp.exe',
            'default_status' => 'active',
        ]);
    }

    // ─── UPDATE STATUS ────────────────────────────────────────────────

    public function test_update_status_changes_default_status(): void
    {
        $sw = SerialSoftware::factory()->active()->create();

        $response = $this->actingAs($this->admin)
            ->patch(route('admin.serial-softwares.status', $sw->id), [
                'status' => 'inactive',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('serial_softwares', [
            'id' => $sw->id,
            'default_status' => 'inactive',
        ]);
    }

    // ─── DESTROY ──────────────────────────────────────────────────────

    public function test_destroy_deletes_software(): void
    {
        $sw = SerialSoftware::factory()->create();

        $response = $this->actingAs($this->admin)
            ->delete(route('admin.serial-softwares.destroy', $sw->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('serial_softwares', ['id' => $sw->id]);
    }

    // ─── EXPORT ───────────────────────────────────────────────────────

    public function test_export_returns_csv(): void
    {
        $sw = SerialSoftware::factory()->create(['name' => 'ExportTest.exe']);
        SerialDevice::factory()->count(2)->active()->create(['serial_software_id' => $sw->id]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.serial-softwares.export'));

        $response->assertSuccessful();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $response->assertDownload();
    }

    // ─── AUTH GUARD ───────────────────────────────────────────────────

    public function test_guest_cannot_access_index(): void
    {
        $response = $this->get(route('admin.serial-softwares.index'));
        $response->assertRedirect('/login');
    }

    public function test_non_admin_cannot_access_index(): void
    {
        $user = User::factory()->create([
            'role' => 'client',
            'onboarding_completed' => true,
        ]);

        $response = $this->actingAs($user)->get(route('admin.serial-softwares.index'));
        $response->assertStatus(403);
    }
}
