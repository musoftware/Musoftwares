<?php

namespace Tests\Unit\App\Services;

use Tests\TestCase;
use App\Services\RecurringService;
use App\Models\RecurringCost;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use Mockery;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RecurringServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_exception_handling_during_entry_processing_logs_error()
    {
        $currencyId = DB::table('currencies')->insertGetId([
            'currency' => 'USD',
            'symbol' => '$',
            'string_format' => '$%01.2f'
        ]);

        $costId = DB::table('recurring_costs')->insertGetId([
            'title' => 'Test Server Cost',
            'reason' => 'Test Server Cost',
            'amount' => 50,
            'currency_id' => $currencyId,
            'recurring' => 'month',
            'recurring_times' => 1,
            'recurring_times_month' => now()->format('d'),
            'current_date' => now()->subMonth()->toDateString(),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Log::shouldReceive('error')
           ->once()
           ->withArgs(function ($message) use ($costId) {
               return str_contains($message, "Failed to process recurring cost {$costId}:");
           });

        // Drop the table to cause CostTransaction::add_cost_balance to throw an exception
        \Illuminate\Support\Facades\Schema::dropIfExists('cost_transactions');

        $service = new RecurringService();
        $service->processDueEntries();
        
        $this->assertTrue(true); // Verification is handled by Mockery
    }
}
