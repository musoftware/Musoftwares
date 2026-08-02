<?php

namespace Tests\Feature\Admin;

use App\Models\SerialSoftware;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SerialSoftwareControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $clientUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create(['onboarding_completed' => true]);
        $this->admin->assignRole('admin');

        $this->clientUser = User::factory()->create(['onboarding_completed' => true]);
        $this->clientUser->assignRole('client');
    }

    public function test_admin_can_view_serial_softwares_index()
    {
        $response = $this->actingAs($this->admin)->get('/admin/serial-softwares');
        $response->assertStatus(200);
    }

    public function test_non_admin_cannot_view_serial_softwares_index()
    {
        $response = $this->actingAs($this->clientUser)->get('/admin/serial-softwares');
        $response->assertStatus(403);
    }

    public function test_admin_can_store_serial_software()
    {
        $response = $this->actingAs($this->admin)->post('/admin/serial-softwares', [
            'name' => 'New Software',
            'default_status' => 'active',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('serial_softwares', [
            'name' => 'New Software',
            'default_status' => 'active',
        ]);
    }

    public function test_admin_can_update_serial_software_status()
    {
        $software = SerialSoftware::create([
            'name' => 'Test Software',
            'default_status' => 'active',
        ]);

        // Assuming route is put/patch to /admin/serial-softwares/{id} or /admin/serial-softwares/{id}/status
        // I'll test the method via the typical route convention, if the route varies we'll fix it. Let me try standard resourceful update.
        // wait, the method is updateStatus. Usually it's mapped to PUT /admin/serial-softwares/{serialSoftware}
        $response = $this->actingAs($this->admin)->patch("/admin/serial-softwares/{$software->id}/status", [
            'status' => 'inactive',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('serial_softwares', [
            'id' => $software->id,
            'default_status' => 'inactive',
        ]);
    }

    public function test_admin_can_delete_serial_software()
    {
        $software = SerialSoftware::create([
            'name' => 'Test Software',
            'default_status' => 'active',
        ]);

        $response = $this->actingAs($this->admin)->delete("/admin/serial-softwares/{$software->id}");

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertSoftDeleted('serial_softwares', [
            'id' => $software->id,
        ]);
    }

    public function test_admin_can_export_serial_softwares()
    {
        $response = $this->actingAs($this->admin)->get('/admin/serial-softwares/export');
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }
}
