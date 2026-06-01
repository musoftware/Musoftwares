<?php

namespace Tests\Unit\App\Services;

use Tests\TestCase;
use App\Services\RecurringService;
use Modules\ERP\Models\RecurringEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Exception;
use Mockery;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class RecurringServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_exception_handling_during_entry_processing_logs_error()
    {
        $userId = DB::table('users')->insertGetId([
            'name' => 'User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $tenantId = DB::table('erp_tenants')->insertGetId([
            'user_id' => $userId,
            'name' => 'Test',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $currencyId = DB::table('currencies')->insertGetId([
            'currency' => 'USD',
            'symbol' => '$',
            'string_format' => '$%01.2f'
        ]);

        $entryId = DB::table('erp_recurring_entries')->insertGetId([
            'tenant_id' => $tenantId,
            'type' => 'expense',
            'title' => 'Test Expense',
            'description' => 'Test',
            'amount' => 10,
            'currency_id' => $currencyId,
            'business_amount' => 10,
            'business_currency_id' => $currencyId,
            'exchange_rate' => 1,
            'exchange_rate_date' => now()->toDateString(),
            'frequency' => 'monthly',
            'starts_at' => now()->subMonth()->toDateString(),
            'next_run_at' => now()->subDay()->toDateString(),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Spy on the Log facade to ensure it records the expected error
        Log::shouldReceive('error')
           ->once()
           ->withArgs(function ($message) use ($entryId) {
               return str_contains($message, "Failed to process recurring entry {$entryId}: Simulated failure during execution");
           });

        // Mock the RecurringService to throw an exception in executeEntry,
        // avoiding DB mocking issues entirely.
        $service = Mockery::mock(RecurringService::class)->makePartial();
        $service->shouldReceive('executeEntry')
                ->once()
                ->andThrow(new Exception('Simulated failure during execution'));

        $service->processDueEntries();

        $entry = DB::table('erp_recurring_entries')->find($entryId);
        $this->assertEquals(1, $entry->is_active);
    }
}
