<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\ERP\Models\Tenant;
use Modules\ERP\Models\TenantClient;
use Modules\ERP\Models\Invoice;
use App\Models\Currency;
use App\Services\ReportService;
use Tests\TestCase;

class ReportServiceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Tenant $tenant;
    protected TenantClient $client;
    protected Currency $currency;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\CurrenciesSeeder::class);

        $this->user = User::factory()->create(['onboarding_completed' => true]);
        $this->user->assignRole('client');

        $this->tenant = Tenant::create([
            'user_id' => $this->user->id,
            'name' => 'Test Tenant',
            'status' => 'active',
        ]);

        $this->client = TenantClient::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Acme Corp',
            'email' => 'billing@acme.com',
            'phone' => '+15551234567',
            'currency' => 'USD',
            'address' => '123 Main St, Anytown',
        ]);

        $this->currency = Currency::firstOrCreate(
            ['currency' => 'USD'],
            ['symbol' => '$', 'string_format' => '$%01.2f']
        );
    }

    public function test_get_pnl_report_executes_successfully(): void
    {
        // Create a paid invoice for the tenant
        $invoice = Invoice::create([
            'tenant_id' => $this->tenant->id,
            'invoice_number' => 'INV-TEST-001',
            'client_id' => $this->client->id,
            'status' => 'paid',
            'amount' => 500.00,
            'amount_currency' => 'USD',
            'business_amount' => 500.00,
            'business_currency' => 'USD',
            'exchange_rate' => 1.0,
            'exchange_rate_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'paid_amount' => 500.00,
            'paid_at' => now(),
            'created_by' => $this->user->id,
        ]);

        $service = new ReportService();
        $report = $service->getPnlReport(
            now()->startOfMonth()->toDateString(),
            now()->endOfMonth()->toDateString()
        );

        $this->assertIsArray($report);
        $this->assertArrayHasKey('tenantStats', $report);
        
        $tenantStats = $report['tenantStats'];
        $this->assertCount(1, $tenantStats);
        $this->assertEquals('Test Tenant', $tenantStats[0]->tenant_name);
        $this->assertEquals(500.00, $tenantStats[0]->revenue);
    }

    public function test_get_pnl_report_includes_main_invoices_grouped_as_main(): void
    {
        // Create a paid invoice with NULL tenant_id
        Invoice::create([
            'tenant_id' => null,
            'invoice_number' => 'INV-MAIN-001',
            'client_id' => $this->client->id, // Use client->id instead of user->id
            'status' => 'paid',
            'amount' => 750.00,
            'amount_currency' => 'USD',
            'business_amount' => 750.00,
            'business_currency' => 'USD',
            'exchange_rate' => 1.0,
            'exchange_rate_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'paid_amount' => 750.00,
            'paid_at' => now(),
            'created_by' => $this->user->id,
        ]);

        $service = new ReportService();
        $report = $service->getPnlReport(
            now()->startOfMonth()->toDateString(),
            now()->endOfMonth()->toDateString()
        );

        $this->assertIsArray($report);
        $this->assertArrayHasKey('tenantStats', $report);

        $tenantStats = $report['tenantStats'];
        $this->assertCount(1, $tenantStats);
        $this->assertEquals('System/Main', $tenantStats[0]->tenant_name);
        $this->assertEquals(750.00, $tenantStats[0]->revenue);
    }
}
