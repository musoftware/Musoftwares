<?php

namespace Modules\ERP\Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\ERP\Models\Tenant;
use Modules\ERP\Models\TenantFile;
use Modules\ERP\Models\TenantStorageProvider;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class FileControllerTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;
    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->tenant = Tenant::create([
            'user_id' => $this->user->id,
            'name' => 'Test Business',
            'base_currency_id' => 1,
            'domain' => 'test' . rand(1000, 9999),
        ]);
    }

    private function giveSubscription()
    {
        UserSubscription::create([
            'user_id' => $this->user->id,
            'object' => 'erp',
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => now()->addYear(),
        ]);
        UserSubscription::create([
            'user_id' => $this->user->id,
            'object' => 'erp-document-storage',
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => now()->addYear(),
        ]);
    }

    public function test_user_without_subscription_can_access_index_but_gets_no_feature()
    {
        UserSubscription::create([
            'user_id' => $this->user->id,
            'object' => 'erp',
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => now()->addYear(),
        ]);

        $response = $this->actingAs($this->user)->get(route('erp.files.index'));
        $response->assertStatus(200);
        // We know it renders successfully even without feature (shows upgrade overlay)
    }

    public function test_user_can_access_files_index()
    {
        $this->giveSubscription();

        $response = $this->actingAs($this->user)->get(route('erp.files.index'));
        $response->assertStatus(200);
    }

    public function test_cannot_upload_without_provider()
    {
        $this->giveSubscription();

        Storage::fake('tenant_s3');
        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->actingAs($this->user)->post(route('erp.files.store'), [
            'file' => $file,
            'type' => 'Invoice',
        ]);

        $response->assertSessionHasErrors(['error']);
    }

    public function test_can_upload_with_provider()
    {
        $this->giveSubscription();

        TenantStorageProvider::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test S3',
            'driver' => 's3',
            'key' => 'key123',
            'secret' => 'secret123',
            'region' => 'us-east-1',
            'bucket' => 'test-bucket',
            'is_default' => true,
        ]);

        Storage::fake('tenant_s3');
        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->actingAs($this->user)->post(route('erp.files.store'), [
            'file' => $file,
            'type' => 'Invoice',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('erp_tenant_files', [
            'tenant_id' => $this->tenant->id,
            'name' => 'document.pdf',
        ]);
    }

    public function test_can_delete_file()
    {
        $this->giveSubscription();

        $provider = TenantStorageProvider::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test S3',
            'driver' => 's3',
            'key' => 'key123',
            'secret' => 'secret123',
            'region' => 'us-east-1',
            'bucket' => 'test-bucket',
            'is_default' => true,
        ]);

        Storage::fake('tenant_s3');
        
        $file = TenantFile::create([
            'tenant_id' => $this->tenant->id,
            'storage_provider_id' => $provider->id,
            'name' => 'test.pdf',
            'path' => 'test/test.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1234,
            'folder' => 'Invoice',
        ]);

        $response = $this->actingAs($this->user)->delete(route('erp.files.destroy', $file));
        
        $response->assertSessionHas('success');
        $this->assertSoftDeleted('erp_tenant_files', [
            'id' => $file->id,
        ]);
    }
}
