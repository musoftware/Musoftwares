<?php

namespace Tests\Feature;

use App\Models\CostTransaction;
use App\Models\Currency;
use App\Models\Transaction;
use App\Models\User;
use App\Services\ReportService;
use Database\Seeders\CurrenciesSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReportServiceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Currency $currency;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(CurrenciesSeeder::class);

        $this->user = User::factory()->create(['onboarding_completed' => true]);
        $this->user->assignRole('client');

        $this->currency = Currency::firstOrCreate(
            ['currency' => 'USD'],
            ['symbol' => '$', 'string_format' => '$%01.2f']
        );
    }

    public function test_get_pnl_report_executes_successfully(): void
    {
        // Create a 'received' transaction to simulate income
        $t1 = new Transaction;
        $t1->user_id = $this->user->id;
        $t1->amount = 500.00;
        $t1->type = 'received';
        $t1->reason = 'Invoice Payment';
        $t1->currency_id = $this->currency->id;
        $t1->created_at = now();
        $t1->save(['timestamps' => false]);

        // Create a 'cost_transaction' to simulate expenses
        $c1 = new CostTransaction;
        $c1->reason = 'Server Costs';
        $c1->amount = 200.00;
        $c1->currency_id = $this->currency->id;
        $c1->created_at = now();
        $c1->save(['timestamps' => false]);

        $service = new ReportService;
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
        $t2 = new Transaction;
        $t2->user_id = $this->user->id;
        $t2->amount = 750.00;
        $t2->type = 'received';
        $t2->reason = 'Old Payment';
        $t2->currency_id = $this->currency->id;
        $t2->save();

        DB::table('transactions')
            ->where('id', $t2->id)
            ->update(['created_at' => now()->subMonths(2)]);

        $service = new ReportService;
        $report = $service->getPnlReport(
            now()->startOfMonth()->toDateString(),
            now()->endOfMonth()->toDateString()
        );

        $this->assertIsArray($report);
        $this->assertEquals(0, $report['totalIncome']);
    }
}
