<?php

namespace Modules\ERP\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Modules\ERP\Models\Tenant;
use Modules\ERP\Models\TenantClient;
use Modules\ERP\Models\Project;
use Tests\TestCase;

class BackupControllerTest extends TestCase
{
    use DatabaseTransactions;
    protected function setUp(): void
    {
        parent::setUp();

        // Removed migrate:fresh to protect the live database schema
        $this->withoutMiddleware();
    }

    public function test_user_without_subscription_cannot_download_backup(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::create(['user_id' => $user->id, 'name' => 'Acme Corp', 'status' => 'active']);
        
        // No subscription added...
        
        $response = $this->actingAs($user)->withSession(['tenant_id' => $tenant->id])->get("/erp/backup/download");
        $response->assertStatus(403);
    }

    public function test_user_can_download_backup(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::create(['user_id' => $user->id, 'name' => 'Acme Corp', 'status' => 'active']);
        
        \App\Models\UserSubscription::create([
            'user_id' => $user->id,
            'object' => 'erp-backup',
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => now()->addYear(),
        ]);
        
        $client = TenantClient::create(['tenant_id' => $tenant->id, 'name' => 'Test Client', 'email' => 'test@example.com', 'currency_id' => 1]);

        $response = $this->actingAs($user)->withSession(['tenant_id' => $tenant->id])->get("/erp/backup/download");
        $response->assertStatus(200);
        
        // Assert it returns a file download
        $response->assertDownload();
    }

    public function test_user_can_restore_backup(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::create(['user_id' => $user->id, 'name' => 'Acme Corp', 'status' => 'active']);
        
        \App\Models\UserSubscription::create([
            'user_id' => $user->id,
            'object' => 'erp-backup',
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => now()->addYear(),
        ]);
        
        // Create initial data to backup
        $client = TenantClient::create(['tenant_id' => $tenant->id, 'name' => 'Original Client', 'email' => 'test@example.com', 'currency_id' => 1]);
        $project = Project::create([
            'tenant_id' => $tenant->id,
            'client_id' => $client->id,
            'name' => 'Test Project',
            'status' => 'Active',
            'budget' => 1000.00,
            'currency_id' => 1,
        ]);

        // Backup
        $backupService = new \Modules\ERP\Services\BackupService();
        $backupPath = $backupService->createBackup($tenant);
        
        $this->assertFileExists($backupPath);

        // Delete the data
        $project->delete();
        $client->delete();
        
        $this->assertDatabaseMissing('erp_tenant_clients', ['id' => $client->id]);
        $this->assertDatabaseMissing('erp_projects', ['id' => $project->id]);

        // Upload and Restore
        $file = new UploadedFile($backupPath, 'backup.json', 'application/json', null, true);

        $response = $this->actingAs($user)->withSession(['tenant_id' => $tenant->id])->post("/erp/backup/restore", [
            'backup_file' => $file,
        ]);

        $response->assertSessionHas('success');

        // Check if data is back
        $this->assertDatabaseHas('erp_tenant_clients', ['name' => 'Original Client']);
        $this->assertDatabaseHas('erp_projects', ['name' => 'Test Project']);
        
        File::delete($backupPath);
    }
}
