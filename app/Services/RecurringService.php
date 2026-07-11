<?php

namespace App\Services;

use App\Models\RecurringCost;
use App\Models\RecurringIncome;
use App\Models\RecurringSalary;
use Illuminate\Support\Facades\Log;

class RecurringService extends BaseService
{
    /**
     * Process all due recurring entries.
     */
    public function processDueEntries(): void
    {
        // 1. Process platform recurring costs
        foreach (RecurringCost::where('is_active', true)->get() as $item) {
            try {
                $item->apply();
            } catch (\Exception $e) {
                Log::error("Failed to process recurring cost {$item->id}: ".$e->getMessage());
            }
        }

        // 2. Process platform recurring incomes
        foreach (RecurringIncome::where('is_active', true)->get() as $item) {
            try {
                $item->apply();
            } catch (\Exception $e) {
                Log::error("Failed to process recurring income {$item->id}: ".$e->getMessage());
            }
        }

        // 3. Process platform recurring salaries
        foreach (RecurringSalary::where('is_active', true)->get() as $item) {
            try {
                $item->apply();
            } catch (\Exception $e) {
                Log::error("Failed to process recurring salary {$item->id}: ".$e->getMessage());
            }
        }

        // Note: ERP recurring entries (erp_recurring_entries) are processed separately
        // via the `erp:process-recurring` Artisan command (Modules\ERP\Console\ProcessRecurringEntries).
        // The ERP module is fully self-contained and manages its own recurring execution.
    }
}
