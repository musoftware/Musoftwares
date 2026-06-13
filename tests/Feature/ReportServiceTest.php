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
        // Create a 'received' transaction to simulate income
        $t1 = new \App\Models\Transaction();
        $t1->user_id = $this->user->id;
        $t1->amount = 500.00;
        $t1->type = 'received';
        $t1->reason = 'Invoice Payment';
        $t1->currency_id = $this->currency->id;
        $t1->created_at = now();
        $t1->save(['timestamps' => false]);

        // Create a 'cost_transaction' to simulate expenses
        $c1 = new \App\Models\CostTransaction();
        $c1->reason = 'Server Costs';
        $c1->amount = 200.00;
        $c1->currency_id = $this->currency->id;
        $c1->created_at = now();
        $c1->save(['timestamps' => false]);

        $service = new ReportService();
        $report = $service->getPnlReport(
            now()->startOfMonth()->toDateString(),
            now()->endOfMonth()->toDateString()
        );

        $this->assertIsArray($report);
        $this->assertArrayHasKey('totalIncome', $report);
        $this->assertArrayHasKey('totalExpenses', $report);
        $this->assertArrayHasKey('netProfit', $report);
        
        $this->assertEquals(500.00, $report['totalIncome']);
        $this->assertEquals(200.00, $report['totalExpenses']);
        $this->assertEquals(300.00, $report['netProfit']);
    }

    public function test_get_pnl_report_filters_by_date(): void
    {
        // Create an old 'received' transaction (last month)
        $t2 = new \App\Models\Transaction();
        $t2->user_id = $this->user->id;
        $t2->amount = 750.00;
        $t2->type = 'received';
        $t2->reason = 'Old Payment';
        $t2->currency_id = $this->currency->id;
        $t2->save();
        
        \Illuminate\Support\Facades\DB::table('transactions')
            ->where('id', $t2->id)
            ->update(['created_at' => now()->subMonths(2)]);

        $service = new ReportService();
        $report = $service->getPnlReport(
            now()->startOfMonth()->toDateString(),
            now()->endOfMonth()->toDateString()
        );

        $this->assertIsArray($report);
        $this->assertEquals(0, $report['totalIncome']);
    }
}
