<?php

namespace Modules\ERP\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Modules\ERP\Models\TenantClient;
use Modules\ERP\Models\Tenant;
use Modules\ERP\Models\Project;
use Modules\ERP\Models\WalletTransaction;
use Tests\TestCase;

class ProjectControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate:fresh', [
            '--path' => [
                'database/migrations',
                'Modules/Core/Database/Migrations',
                'Modules/ERP/Database/Migrations',
            ]
        ]);
        
        $this->seed(\Database\Seeders\CurrenciesSeeder::class);
    }

    public function test_project_creation_inherits_client_currency(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::create(['user_id' => $user->id, 'name' => 'Test Tenant', 'status' => 'active']);
        
        // Create client with currency_id = 2 (e.g. EUR or another currency from seed)
        $client = TenantClient::create([
            'tenant_id' => $tenant->id,
            'name' => 'Euro Client',
            'email' => 'euro@example.com',
            'currency_id' => 2,
        ]);

        $response = $this->actingAs($user)->withSession(['tenant_id' => $tenant->id])->post('/erp/projects', [
            'name' => 'Euro Project',
            'client_id' => $client->id,
            'budget' => 5000.00,
            'leader' => 'Leader Name',
            'status' => 'Active',
        ]);

        $response->assertRedirect();
        
        $project = Project::where('name', 'Euro Project')->first();
        $this->assertNotNull($project);
        // Verify project currency is identical to client currency
        $this->assertEquals($client->currency_id, $project->currency_id);
    }

    public function test_project_show_page_loads_with_related_data_and_transactions(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::create(['user_id' => $user->id, 'name' => 'Acme Tenant', 'status' => 'active']);
        $client = TenantClient::create(['tenant_id' => $tenant->id, 'name' => 'Client', 'email' => 'client@test.com', 'currency_id' => 1]);
        
        $project = Project::create([
            'tenant_id' => $tenant->id,
            'client_id' => $client->id,
            'name' => 'Test Show Project',
            'status' => 'Active',
            'budget' => 1200.00,
            'leader' => 'John Doe',
            'currency_id' => 1,
        ]);

        // Create transaction linked to project
        $transaction = WalletTransaction::create([
            'tenant_id' => $tenant->id,
            'client_id' => $client->id,
            'project_id' => $project->id,
            'type' => 'manual_credit',
            'direction' => 'credit',
            'amount' => 300.00,
            'currency_id' => 1,
            'business_amount' => 300.00,
            'business_currency_id' => 1,
            'exchange_rate' => 1.0,
            'exchange_rate_date' => now()->toDateString(),
            'reference_type' => 'manual_credit',
            'reference_id' => $user->id,
            'note' => 'Project ledger adjustment',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->get("/erp/projects/{$project->id}");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('ERP/Projects/Show')
            ->has('project')
            ->has('transactions')
            ->has('stats')
        );

        $inertiaTransactions = $response->original->getData()['page']['props']['transactions'];
        $this->assertCount(1, $inertiaTransactions);
        $this->assertEquals($transaction->id, $inertiaTransactions[0]['id']);
    }

    public function test_invoice_create_route_with_project_preselects_client_and_project(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::create(['user_id' => $user->id, 'name' => 'Acme Corp', 'status' => 'active']);
        $client = TenantClient::create(['tenant_id' => $tenant->id, 'name' => 'Client A', 'email' => 'clienta@test.com', 'currency_id' => 1]);
        
        $project = Project::create([
            'tenant_id' => $tenant->id,
            'client_id' => $client->id,
            'name' => 'Project A',
            'status' => 'Active',
            'budget' => 2000.00,
            'leader' => 'Leader',
            'currency_id' => 1,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->get("/erp/invoices/create?project_id={$project->id}");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('ERP/Invoices/Create')
            ->where('pre_selected_client_id', $client->id)
            ->where('pre_selected_project_id', $project->id)
        );
    }

    public function test_project_update_successfully(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::create(['user_id' => $user->id, 'name' => 'Acme Corp', 'status' => 'active']);
        $client = TenantClient::create(['tenant_id' => $tenant->id, 'name' => 'Client A', 'email' => 'clienta@test.com', 'currency_id' => 1]);
        
        $project = Project::create([
            'tenant_id' => $tenant->id,
            'client_id' => $client->id,
            'name' => 'Old Project Name',
            'status' => 'Planning',
            'budget' => 2000.00,
            'leader' => 'Leader',
            'currency_id' => 1,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->put("/erp/projects/{$project->id}", [
                'name' => 'New Project Name',
                'client_id' => $client->id,
                'status' => 'Active',
                'budget' => 2500.00,
                'due_date' => now()->addDays(10)->toDateString(),
            ]);

        $response->assertRedirect(route('erp.dashboard', ['section' => 'projects']));
        
        $project->refresh();
        $this->assertEquals('New Project Name', $project->name);
        $this->assertEquals('Active', $project->status);
        $this->assertEquals(2500.00, (float) $project->budget);
    }

    public function test_project_delete_successfully(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::create(['user_id' => $user->id, 'name' => 'Acme Corp', 'status' => 'active']);
        $client = TenantClient::create(['tenant_id' => $tenant->id, 'name' => 'Client A', 'email' => 'clienta@test.com', 'currency_id' => 1]);
        
        $project = Project::create([
            'tenant_id' => $tenant->id,
            'client_id' => $client->id,
            'name' => 'Project to Delete',
            'status' => 'Planning',
            'budget' => 2000.00,
            'leader' => 'Leader',
            'currency_id' => 1,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->from(route('erp.dashboard', ['section' => 'projects']))
            ->delete("/erp/projects/{$project->id}");

        $response->assertRedirect(route('erp.dashboard', ['section' => 'projects']));
        
        $this->assertNull(Project::find($project->id));
    }
}
