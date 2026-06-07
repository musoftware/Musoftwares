<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\File;
use App\Models\FileFolder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserFileControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $clientUser;
    protected User $otherClientUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create(['onboarding_completed' => true]);
        $this->admin->assignRole('admin');

        $this->clientUser = User::factory()->create(['onboarding_completed' => true]);
        $this->clientUser->assignRole('client');
        
        $this->otherClientUser = User::factory()->create(['onboarding_completed' => true]);
        $this->otherClientUser->assignRole('client');
        
        Storage::fake('uploaded_user_files');
    }

    public function test_admin_can_view_user_files_root()
    {
        $response = $this->actingAs($this->admin)->get("/admin/users/{$this->clientUser->id}/files");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Users/Files')
            ->has('files')
            ->has('folders')
            ->has('breadcrumbs')
        );
    }

    public function test_admin_can_view_user_files_inside_child_folder()
    {
        $folder = new FileFolder();
        $folder->user_id = $this->clientUser->id;
        $folder->foldername = 'ParentFolder';
        $folder->save();

        $childFolder = new FileFolder();
        $childFolder->user_id = $this->clientUser->id;
        $childFolder->folder_id = $folder->id;
        $childFolder->foldername = 'ChildFolder';
        $childFolder->save();

        $response = $this->actingAs($this->admin)->get("/admin/users/{$this->clientUser->id}/files?folder=folder_{$childFolder->id}");
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('breadcrumbs', 3) // Root > ParentFolder > ChildFolder
        );
    }

    public function test_non_admin_cannot_view_user_files()
    {
        $response = $this->actingAs($this->clientUser)->get("/admin/users/{$this->clientUser->id}/files");
        $response->assertStatus(403);
    }

    public function test_admin_can_upload_file_to_root()
    {
        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $response = $this->actingAs($this->admin)->post("/admin/users/{$this->clientUser->id}/files/upload", [
            'file' => $file,
            'folder' => ''
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('files', [
            'user_id' => $this->clientUser->id,
            'folder_id' => null,
            'original_filename' => 'document.pdf',
        ]);
        
        $fileModel = File::where('original_filename', 'document.pdf')->first();
        Storage::disk('uploaded_user_files')->assertExists($fileModel->filename);
    }

    public function test_admin_can_upload_file_to_child_folder()
    {
        $folder = new FileFolder();
        $folder->user_id = $this->clientUser->id;
        $folder->foldername = 'MyDocs';
        $folder->save();

        $file = UploadedFile::fake()->create('report.pdf', 100, 'application/pdf');

        $response = $this->actingAs($this->admin)->post("/admin/users/{$this->clientUser->id}/files/upload", [
            'file' => $file,
            'folder' => 'folder_' . $folder->id
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('files', [
            'user_id' => $this->clientUser->id,
            'folder_id' => $folder->id,
            'original_filename' => 'report.pdf',
        ]);
    }

    public function test_admin_can_create_new_folder_in_root()
    {
        $response = $this->actingAs($this->admin)->post("/admin/users/{$this->clientUser->id}/files/folder", [
            'name' => 'RootFolder',
            'parent' => ''
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('file_folders', [
            'user_id' => $this->clientUser->id,
            'folder_id' => null,
            'foldername' => 'RootFolder',
        ]);
    }

    public function test_admin_can_create_new_child_folder_inside_parent_folder()
    {
        $folder = new FileFolder();
        $folder->user_id = $this->clientUser->id;
        $folder->foldername = 'Parent';
        $folder->save();

        $response = $this->actingAs($this->admin)->post("/admin/users/{$this->clientUser->id}/files/folder", [
            'name' => 'ChildFolder',
            'parent' => 'folder_' . $folder->id
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('file_folders', [
            'user_id' => $this->clientUser->id,
            'folder_id' => $folder->id,
            'foldername' => 'ChildFolder',
        ]);
    }

    public function test_admin_can_rename_file_and_folder()
    {
        // Setup file
        $file = new File();
        $file->user_id = $this->clientUser->id;
        $file->filename = 'test.pdf';
        $file->original_filename = 'old.pdf';
        $file->size = 100;
        $file->url = 'test.url';
        $file->save();

        // Setup folder
        $folder = new FileFolder();
        $folder->user_id = $this->clientUser->id;
        $folder->foldername = 'OldFolder';
        $folder->save();

        // Rename file
        $response1 = $this->actingAs($this->admin)->post("/admin/users/{$this->clientUser->id}/files/rename", [
            'path' => 'file_' . $file->id,
            'new_name' => 'new.pdf'
        ]);
        $response1->assertRedirect();
        $response1->assertSessionHas('success');

        // Rename folder
        $response2 = $this->actingAs($this->admin)->post("/admin/users/{$this->clientUser->id}/files/rename", [
            'path' => 'folder_' . $folder->id,
            'new_name' => 'NewFolder'
        ]);
        $response2->assertRedirect();
        $response2->assertSessionHas('success');

        $this->assertDatabaseHas('files', [
            'id' => $file->id,
            'original_filename' => 'new.pdf',
        ]);
        $this->assertDatabaseHas('file_folders', [
            'id' => $folder->id,
            'foldername' => 'NewFolder',
        ]);
    }

    public function test_admin_can_move_file_and_folder_to_child_folder()
    {
        $file = new File();
        $file->user_id = $this->clientUser->id;
        $file->filename = 'test.pdf';
        $file->original_filename = 'file.pdf';
        $file->size = 100;
        $file->url = 'test.url';
        $file->save();

        $subFolderToMove = new FileFolder();
        $subFolderToMove->user_id = $this->clientUser->id;
        $subFolderToMove->foldername = 'SubFolder';
        $subFolderToMove->save();

        $destinationFolder = new FileFolder();
        $destinationFolder->user_id = $this->clientUser->id;
        $destinationFolder->foldername = 'DestFolder';
        $destinationFolder->save();

        $response = $this->actingAs($this->admin)->post("/admin/users/{$this->clientUser->id}/files/move", [
            'paths' => ['file_' . $file->id, 'folder_' . $subFolderToMove->id],
            'destination' => 'folder_' . $destinationFolder->id
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('files', [
            'id' => $file->id,
            'folder_id' => $destinationFolder->id,
        ]);
        $this->assertDatabaseHas('file_folders', [
            'id' => $subFolderToMove->id,
            'folder_id' => $destinationFolder->id,
        ]);
    }

    public function test_admin_can_delete_file_and_folder()
    {
        // File
        $file = new File();
        $file->user_id = $this->clientUser->id;
        $file->filename = 'test_delete.pdf';
        $file->original_filename = 'delete.pdf';
        $file->size = 100;
        $file->url = 'test.url';
        $file->save();
        Storage::disk('uploaded_user_files')->put('test_delete.pdf', 'content');

        // Folder with child
        $folder = new FileFolder();
        $folder->user_id = $this->clientUser->id;
        $folder->foldername = 'DeleteMe';
        $folder->save();

        $childFile = new File();
        $childFile->user_id = $this->clientUser->id;
        $childFile->folder_id = $folder->id;
        $childFile->filename = 'child_delete.pdf';
        $childFile->original_filename = 'child.pdf';
        $childFile->size = 100;
        $childFile->url = 'test.url';
        $childFile->save();
        Storage::disk('uploaded_user_files')->put('child_delete.pdf', 'content');

        $response = $this->actingAs($this->admin)->delete("/admin/users/{$this->clientUser->id}/files", [
            'paths' => ['file_' . $file->id, 'folder_' . $folder->id]
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('files', ['id' => $file->id]);
        $this->assertDatabaseMissing('file_folders', ['id' => $folder->id]);
        $this->assertDatabaseMissing('files', ['id' => $childFile->id]);

        Storage::disk('uploaded_user_files')->assertMissing('test_delete.pdf');
        Storage::disk('uploaded_user_files')->assertMissing('child_delete.pdf');
    }

    public function test_admin_can_download_single_file()
    {
        $file = new File();
        $file->user_id = $this->clientUser->id;
        $file->filename = 'test_download.pdf';
        $file->original_filename = 'download.pdf';
        $file->size = 100;
        $file->url = 'test.url';
        $file->save();

        Storage::disk('uploaded_user_files')->put('test_download.pdf', 'hello world');

        $response = $this->actingAs($this->admin)->get("/admin/users/{$this->clientUser->id}/files/download?paths[]=file_{$file->id}");

        $response->assertStatus(200);
        $response->assertDownload('download.pdf');
    }

    public function test_admin_can_download_multiple_files_and_folders_as_zip()
    {
        if (!class_exists('ZipArchive')) {
            $this->markTestSkipped('ZipArchive extension is not enabled.');
        }
        $file = new File();
        $file->user_id = $this->clientUser->id;
        $file->filename = 'test_download_1.pdf';
        $file->original_filename = 'doc1.pdf';
        $file->size = 100;
        $file->url = 'test.url';
        $file->save();
        Storage::disk('uploaded_user_files')->put('test_download_1.pdf', 'file 1 content');

        $folder = new FileFolder();
        $folder->user_id = $this->clientUser->id;
        $folder->foldername = 'Archive';
        $folder->save();

        $childFile = new File();
        $childFile->user_id = $this->clientUser->id;
        $childFile->folder_id = $folder->id;
        $childFile->filename = 'test_download_2.pdf';
        $childFile->original_filename = 'doc2.pdf';
        $childFile->size = 100;
        $childFile->url = 'test.url';
        $childFile->save();
        Storage::disk('uploaded_user_files')->put('test_download_2.pdf', 'file 2 content');

        $response = $this->actingAs($this->admin)->get("/admin/users/{$this->clientUser->id}/files/download?paths[]=file_{$file->id}&paths[]=folder_{$folder->id}");

        $response->assertStatus(200);
        $response->assertDownload('files.zip');
    }

    public function test_admin_cannot_manipulate_files_belonging_to_other_users()
    {
        // Try to access otherClientUser's file while hitting clientUser's endpoint
        $file = new File();
        $file->user_id = $this->otherClientUser->id;
        $file->filename = 'other_user_file.pdf';
        $file->original_filename = 'secret.pdf';
        $file->size = 100;
        $file->url = 'test.url';
        $file->save();

        // Download Attempt
        $response1 = $this->actingAs($this->admin)->get("/admin/users/{$this->clientUser->id}/files/download?paths[]=file_{$file->id}");
        $response1->assertStatus(404);
        // It returns a zip, but it will be empty because File::where('user_id', $userId) fails
        
        // Delete Attempt
        $response2 = $this->actingAs($this->admin)->delete("/admin/users/{$this->clientUser->id}/files", [
            'paths' => ['file_' . $file->id]
        ]);
        $response2->assertRedirect();
        $this->assertDatabaseHas('files', ['id' => $file->id]); // file should still exist!

        // Rename Attempt
        $response3 = $this->actingAs($this->admin)->post("/admin/users/{$this->clientUser->id}/files/rename", [
            'path' => 'file_' . $file->id,
            'new_name' => 'hacked.pdf'
        ]);
        $response3->assertStatus(404);
        $this->assertDatabaseHas('files', ['id' => $file->id, 'original_filename' => 'secret.pdf']);
    }
}
