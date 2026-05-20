<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Modules\ERP\Models\RecurringEntry;
use Modules\ERP\Models\Tenant;
use Tests\TestCase;

class RecurringEntrySchedulerTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->user = User::factory()->create(['onboarding_completed' => true]);
        $this->user->assignRole('client');

        $this->tenant = Tenant::create([
            'user_id' => $this->user->id,
            'name'    => 'Test Corp',
            'status'  => 'active',
        ]);
    }

    public function test_processes_due_recurring_entry_and_creates_log(): void
    {
        // Create a recurring entry that is due today
        $entry = DB::table('recurring_entries')->insertGetId([
            'tenant_id'         => $this->tenant->id,
            'type'              => 'income',
            'title'             => 'Monthly Retainer',
            'amount'            => 500.00,
            'amount_currency'   => 'USD',
            'business_amount'   => 500.00,
            'business_currency' => 'USD',
            'exchange_rate'     => 1.0,
            'exchange_rate_date'=> Carbon::today()->toDateString(),
            'frequency'         => 'monthly',
            'starts_at'         => Carbon::now()->subMonth()->toDateString(),
            'next_run_at'       => Carbon::today()->toDateString(), // due today
            'is_active'         => true,
            'status'            => 'active',
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        $exitCode = Artisan::call('erp:recurring:process');

        $this->assertEquals(0, $exitCode);

        // An execution log must be created
        $this->assertDatabaseHas('recurring_execution_logs', [
            'recurring_entry_id' => $entry,
            'status'             => 'success',
        ]);

        // next_run_at must be advanced by 1 month
        $updated = DB::table('recurring_entries')->where('id', $entry)->first();
        $this->assertEquals(
            Carbon::today()->addMonth()->toDateString(),
            Carbon::parse($updated->next_run_at)->toDateString()
        );
    }

    public function test_skips_inactive_recurring_entries(): void
    {
        DB::table('recurring_entries')->insertGetId([
            'tenant_id'         => $this->tenant->id,
            'type'              => 'expense',
            'title'             => 'Office Rent',
            'amount'            => 1000.00,
            'amount_currency'   => 'USD',
            'business_amount'   => 1000.00,
            'business_currency' => 'USD',
            'exchange_rate'     => 1.0,
            'exchange_rate_date'=> Carbon::today()->toDateString(),
            'frequency'         => 'monthly',
            'starts_at'         => Carbon::now()->subMonth()->toDateString(),
            'next_run_at'       => Carbon::today()->toDateString(),
            'is_active'         => false, // inactive
            'status'            => 'paused',
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        $exitCode = Artisan::call('erp:recurring:process');
        $this->assertEquals(0, $exitCode);

        // No execution logs should be created
        $this->assertDatabaseEmpty('recurring_execution_logs');
    }

    public function test_skips_future_entries(): void
    {
        DB::table('recurring_entries')->insertGetId([
            'tenant_id'         => $this->tenant->id,
            'type'              => 'income',
            'title'             => 'Future Revenue',
            'amount'            => 200.00,
            'amount_currency'   => 'USD',
            'business_amount'   => 200.00,
            'business_currency' => 'USD',
            'exchange_rate'     => 1.0,
            'exchange_rate_date'=> Carbon::today()->toDateString(),
            'frequency'         => 'monthly',
            'starts_at'         => Carbon::today()->toDateString(),
            'next_run_at'       => Carbon::tomorrow()->toDateString(), // NOT due yet
            'is_active'         => true,
            'status'            => 'active',
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        $exitCode = Artisan::call('erp:recurring:process');
        $this->assertEquals(0, $exitCode);

        $this->assertDatabaseEmpty('recurring_execution_logs');
    }

    public function test_dry_run_does_not_create_logs(): void
    {
        DB::table('recurring_entries')->insertGetId([
            'tenant_id'         => $this->tenant->id,
            'type'              => 'income',
            'title'             => 'Dry Run Revenue',
            'amount'            => 300.00,
            'amount_currency'   => 'USD',
            'business_amount'   => 300.00,
            'business_currency' => 'USD',
            'exchange_rate'     => 1.0,
            'exchange_rate_date'=> Carbon::today()->toDateString(),
            'frequency'         => 'weekly',
            'starts_at'         => Carbon::now()->subWeek()->toDateString(),
            'next_run_at'       => Carbon::today()->toDateString(),
            'is_active'         => true,
            'status'            => 'active',
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        $exitCode = Artisan::call('erp:recurring:process', ['--dry-run' => true]);
        $this->assertEquals(0, $exitCode);

        // Dry run must not create any execution logs
        $this->assertDatabaseEmpty('recurring_execution_logs');
    }
}
