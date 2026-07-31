<?php

namespace Tests\Feature;

use App\Models\Currency;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InvoicePerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_index_page_performance_with_large_dataset(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $admin = User::factory()->create(['onboarding_completed' => true]);
        $admin->assignRole('admin');

        $currency = Currency::first() ?? Currency::factory()->create();

        $client = User::factory()->create([
            'onboarding_completed' => true,
            'currency_id' => $currency->id,
        ]);
        $client->assignRole('client');

        $this->outputInfo("Seeding 50,000 invoices and 90,000 transactions...");
        
        $startTime = microtime(true);

        DB::beginTransaction();

        $invoices = [];
        $now = now()->toDateTimeString();
        
        for ($i = 1; $i <= 50000; $i++) {
            $invoices[] = [
                'uuid' => sprintf('88888888-4444-4444-4444-%012x', $i),
                'user_id' => $client->id,
                'project_id' => null,
                'request_id' => null,
                'paid' => 100.0,
                'unpaid' => 50.0,
                'currency_id' => $currency->id,
                'tax_value' => 10.0,
                'discount' => 5.0,
                'status' => 'partially_paid',
                'final_total' => 150.0,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($invoices) >= 500) {
                DB::table('invoices')->insert($invoices);
                $invoices = [];
            }
        }
        if (!empty($invoices)) {
            DB::table('invoices')->insert($invoices);
        }

        $transactions = [];
        for ($i = 1; $i <= 90000; $i++) {
            $transactions[] = [
                'user_id' => $client->id,
                'amount' => 100.0,
                'reason' => 'Transaction ' . $i,
                'type' => 'received',
                'project_id' => null,
                'currency_id' => $currency->id,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($transactions) >= 500) {
                DB::table('transactions')->insert($transactions);
                $transactions = [];
            }
        }
        if (!empty($transactions)) {
            DB::table('transactions')->insert($transactions);
        }

        DB::commit();
        
        $seedingDuration = microtime(true) - $startTime;
        $this->outputInfo("Seeding completed in " . round($seedingDuration, 2) . "s.");

        // Count to verify
        $invoiceCount = DB::table('invoices')->count();
        $transactionCount = DB::table('transactions')->count();
        $this->assertEquals(50000, $invoiceCount);
        $this->assertEquals(90000, $transactionCount);

        // Track executed queries and page load time
        DB::flushQueryLog();
        DB::enableQueryLog();

        $pageStartTime = microtime(true);

        $response = $this->actingAs($admin)->get(route('admin.invoices.index'));

        $pageDuration = microtime(true) - $pageStartTime;
        $queries = DB::getQueryLog();
        $queryCount = count($queries);

        $this->outputInfo("Page load time: " . round($pageDuration * 1000, 2) . "ms");
        $this->outputInfo("Number of database queries: " . $queryCount);
        
        // Print unique queries with count
        $queryCounts = array_count_values(array_column($queries, 'query'));
        arsort($queryCounts);
        $this->outputInfo("Top queries:\n" . json_encode(array_slice($queryCounts, 0, 15, true), JSON_PRETTY_PRINT));

        $response->assertStatus(200);

        // Assert query count is reasonable (should be small, not proportional to 50k, i.e., O(1))
        $this->assertLessThan(60, $queryCount, "N+1 query issue detected! Too many queries: " . $queryCount);
        
        // Assert page duration is reasonable (less than 1500ms in testing environment for 50k data)
        $this->assertLessThan(1.5, $pageDuration, "Page load is too slow: " . round($pageDuration * 1000, 2) . "ms");
    }

    private function outputInfo(string $message): void
    {
        fwrite(STDERR, $message . "\n");
    }
}
