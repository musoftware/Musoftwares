<?php

namespace Tests\Feature\Admin;

use App\Models\ClientVaultAsset;
use App\Models\Currency;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminProjectVaultTest extends TestCase
{
    use RefreshDatabase;

    protected Currency $currency;
    protected User $admin;
    protected User $client;
    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        Storage::fake('local');

        $this->currency = Currency::firstOrCreate(
            ['currency' => 'USD'],
            ['name' => 'US Dollar', 'symbol' => '$', 'string_format' => '%s%.2f']
        );

        $this->admin = User::factory()->create([
            'currency_id' => $this->currency->id,
            'onboarding_completed' => true,
        ]);
        $this->admin->assignRole('admin');

        $this->client = User::factory()->create([
            'currency_id' => $this->currency->id,
            'onboarding_completed' => true,
        ]);
        $this->client->assignRole('client');

        $this->project = Project::create([
            'user_id' => $this->client->id,
            'project_name' => 'Enterprise Architecture Portal',
            'status' => 'open',
            'progress_stage' => 'planning',
            'archived' => 0,
        ]);
    }

    public function test_admin_can_view_project_vault_page(): void
    {
        $response = $this->actingAs($this->admin, 'web')
            ->get(route('admin.projects.vault.index', ['project' => $this->project->id]));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Projects/Vault/Index')
            ->where('project.id', $this->project->id)
            ->where('project.name', 'Enterprise Architecture Portal')
            ->where('project.client_name', $this->client->name)
        );
    }

    public function test_admin_can_upload_vault_deliverable(): void
    {
        $fakeFile = UploadedFile::fake()->create('source_code_v1.zip', 2048, 'application/zip');

        $response = $this->actingAs($this->admin, 'web')
            ->post(route('admin.projects.vault.store', ['project' => $this->project->id]), [
                'title' => 'Complete Source Code Release v1.0',
                'asset_type' => 'source_code',
                'file' => $fakeFile,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('client_vault_assets', [
            'project_id' => $this->project->id,
            'user_id' => $this->client->id,
            'title' => 'Complete Source Code Release v1.0',
            'asset_type' => 'source_code',
        ]);

        $asset = ClientVaultAsset::where('project_id', $this->project->id)->first();
        $this->assertNotNull($asset);
        Storage::disk('local')->assertExists($asset->storage_path);
    }

    public function test_uploaded_deliverable_appears_on_client_dashboard_and_can_be_downloaded(): void
    {
        // 1. Admin uploads deliverable
        $fakeFile = UploadedFile::fake()->create('production_build.exe', 4096, 'application/octet-stream');

        $this->actingAs($this->admin, 'web')
            ->post(route('admin.projects.vault.store', ['project' => $this->project->id]), [
                'title' => 'Production Release Executable',
                'asset_type' => 'delivery_build',
                'file' => $fakeFile,
            ]);

        $asset = ClientVaultAsset::where('project_id', $this->project->id)->first();
        $this->assertNotNull($asset);

        // 2. Client views their dashboard and sees the asset in vaultAssets
        $clientResponse = $this->actingAs($this->client, 'web')->get('/dashboard');
        $clientResponse->assertStatus(200);
        $clientResponse->assertInertia(fn (Assert $page) => $page
            ->component('Client/Dashboard')
            ->has('vaultAssets', 1)
            ->where('vaultAssets.0.id', $asset->id)
            ->where('vaultAssets.0.title', 'Production Release Executable')
        );

        // 3. Client downloads asset through secure portal endpoint
        $downloadRes = $this->actingAs($this->client, 'web')
            ->get("/api/portal/vault/assets/{$asset->id}/download");

        $downloadRes->assertStatus(200);
        $this->assertEquals(1, $asset->fresh()->download_count);
        $this->assertNotNull($asset->fresh()->last_accessed_at);
    }

    public function test_admin_can_download_and_delete_vault_asset(): void
    {
        $fakeFile = UploadedFile::fake()->create('contract.pdf', 1024, 'application/pdf');

        $this->actingAs($this->admin, 'web')
            ->post(route('admin.projects.vault.store', ['project' => $this->project->id]), [
                'title' => 'Signed Project Handoff Deed',
                'asset_type' => 'contract',
                'file' => $fakeFile,
            ]);

        $asset = ClientVaultAsset::where('project_id', $this->project->id)->first();

        // 1. Admin download
        $dl = $this->actingAs($this->admin, 'web')
            ->get(route('admin.projects.vault.download', ['project' => $this->project->id, 'asset' => $asset->id]));
        $dl->assertStatus(200);

        // 2. Admin delete
        $del = $this->actingAs($this->admin, 'web')
            ->delete(route('admin.projects.vault.destroy', ['project' => $this->project->id, 'asset' => $asset->id]));
        $del->assertRedirect();
        $del->assertSessionHas('success');

        $this->assertSoftDeleted('client_vault_assets', ['id' => $asset->id]);
    }
}
