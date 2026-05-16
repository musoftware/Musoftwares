<?php

namespace Tests\Unit\Modules\Core\Services;

use Tests\TestCase;
use Modules\Core\Services\RecurringService;
use Modules\ERP\Models\RecurringEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Exception;
use Mockery;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RecurringServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // We create minimal tables to make eloquent work for this test
        Schema::create('tenants', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('domain');
            $table->timestamps();
        });

        Schema::create('recurring_entries', function ($table) {
            $table->id();
            $table->foreignId('tenant_id');
            $table->string('type');
            $table->string('title');
            $table->string('description');
            $table->decimal('amount', 15, 2);
            $table->string('amount_currency');
            $table->decimal('business_amount', 15, 2);
            $table->string('business_currency');
            $table->string('frequency');
            $table->date('next_date');
            $table->date('end_date')->nullable();
            $table->boolean('is_active');
            $table->timestamps();
        });
    }

    public function test_exception_handling_during_entry_processing_logs_error()
    {
        $tenantId = DB::table('tenants')->insertGetId([
            'name' => 'Test',
            'domain' => 'test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $entryId = DB::table('recurring_entries')->insertGetId([
            'tenant_id' => $tenantId,
            'type' => 'expense',
            'title' => 'Test Expense',
            'description' => 'Test',
            'amount' => 10,
            'amount_currency' => 'USD',
            'business_amount' => 10,
            'business_currency' => 'USD',
            'frequency' => 'monthly',
            'next_date' => now()->subDay()->toDateString(),
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

        $entry = DB::table('recurring_entries')->find($entryId);
        $this->assertEquals(1, $entry->is_active);
    }
}
