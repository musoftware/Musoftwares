<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserFileControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $clientUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create(['onboarding_completed' => true]);
        $this->admin->assignRole('admin');

        $this->clientUser = User::factory()->create(['onboarding_completed' => true]);
        $this->clientUser->assignRole('client');
        
        Storage::fake('local');
    }

    public function test_admin_can_view_user_files()
    {
        $response = $this->actingAs($this->admin)->get("/admin/users/{$this->clientUser->id}/files");

        $response->assertStatus(200);
    }

    public function test_non_admin_cannot_view_user_files()
    {
        $response = $this->actingAs($this->clientUser)->get("/admin/users/{$this->clientUser->id}/files");

        $response->assertStatus(403);
    }

    public function test_admin_can_upload_file()
    {
        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $response = $this->actingAs($this->admin)->post("/admin/users/{$this->clientUser->id}/files/upload", [
            'file' => $file,
            'folder' => ''
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_admin_can_create_new_folder()
    {
        $response = $this->actingAs($this->admin)->post("/admin/users/{$this->clientUser->id}/files/folder", [
            'name' => 'New Folder',
            'parent' => ''
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_admin_can_rename_file()
    {
        Storage::disk('local')->put("user-files/{$this->clientUser->id}/old.pdf", 'content');

        $response = $this->actingAs($this->admin)->post("/admin/users/{$this->clientUser->id}/files/rename", [
            'path' => 'old.pdf',
            'new_name' => 'new.pdf'
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_admin_can_move_file()
    {
        Storage::disk('local')->put("user-files/{$this->clientUser->id}/file.pdf", 'content');
        Storage::disk('local')->makeDirectory("user-files/{$this->clientUser->id}/dest");

        $response = $this->actingAs($this->admin)->post("/admin/users/{$this->clientUser->id}/files/move", [
            'paths' => ['file.pdf'],
            'destination' => 'dest'
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_admin_can_delete_file()
    {
        Storage::disk('local')->put("user-files/{$this->clientUser->id}/delete.pdf", 'content');

        $response = $this->actingAs($this->admin)->delete("/admin/users/{$this->clientUser->id}/files", [
            'paths' => ['delete.pdf']
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_admin_can_download_file()
    {
        Storage::disk('local')->put("user-files/{$this->clientUser->id}/test.txt", 'hello world');

        $response = $this->actingAs($this->admin)->get("/admin/users/{$this->clientUser->id}/files/download?paths[]=test.txt");

        $response->assertStatus(200);
    }
}
