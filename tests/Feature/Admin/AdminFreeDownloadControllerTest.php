<?php

namespace Tests\Feature\Admin;

use App\Models\FreeDownload;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminFreeDownloadControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $clientUser;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('free_downloads');
        Schema::create('free_downloads', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('programming_language')->nullable();
                $table->string('image')->nullable();
                $table->string('file_path')->nullable();
                $table->string('original_filename')->nullable();
                $table->boolean('is_active')->default(true);
                $table->integer('order_column')->default(0);
                $table->timestamps();
                $table->softDeletes();
            });

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create(['onboarding_completed' => true]);
        $this->admin->assignRole('admin');

        $this->clientUser = User::factory()->create(['onboarding_completed' => true]);
        $this->clientUser->assignRole('client');

        Storage::fake('public');
    }

    public function test_admin_can_view_free_downloads_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.free-downloads.index'));
        $response->assertStatus(200);
    }

    public function test_non_admin_cannot_view_free_downloads_index(): void
    {
        $response = $this->actingAs($this->clientUser)->get(route('admin.free-downloads.index'));
        $response->assertStatus(403);
    }

    public function test_admin_can_store_new_free_download(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->actingAs($this->admin)->post(route('admin.free-downloads.store'), [
            'title' => 'Test Download',
            'programming_language' => 'PHP',
            'file' => $file,
            'is_active' => true,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('free_downloads', [
            'title' => 'Test Download',
            'programming_language' => 'PHP',
            'original_filename' => 'document.pdf',
        ]);

        $download = FreeDownload::where('title', 'Test Download')->first();
        Storage::disk('public')->assertExists($download->file_path);
    }

    public function test_store_free_download_requires_title(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.free-downloads.store'), [
            'programming_language' => 'PHP',
        ]);

        $response->assertSessionHasErrors('title');
    }

    public function test_admin_can_update_free_download(): void
    {
        $download = FreeDownload::create([
            'title' => 'Old Title',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->put(route('admin.free-downloads.update', $download->id), [
            'title' => 'New Title',
            'programming_language' => 'PHP',
            'is_active' => false,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('free_downloads', [
            'id' => $download->id,
            'title' => 'New Title',
            'is_active' => false,
        ]);
    }

    public function test_admin_can_delete_free_download(): void
    {
        $download = FreeDownload::create([
            'title' => 'To Delete',
            'file_path' => 'free_downloads/files/test.pdf',
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.free-downloads.destroy', $download->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertSoftDeleted('free_downloads', ['id' => $download->id]);
    }
}
