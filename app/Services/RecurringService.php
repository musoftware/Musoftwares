<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class RecurringService
{
    /**
     * Process all due recurring entries.
     */
    public function processDueEntries(): void
    {
        // 1. Process platform recurring costs
        foreach (\App\Models\RecurringCost::where('is_active', true)->get() as $item) {
            try {
                $item->apply();
            } catch (\Exception $e) {
                Log::error("Failed to process recurring cost {$item->id}: " . $e->getMessage());
            }
        }

        // 2. Process platform recurring incomes
        foreach (\App\Models\RecurringIncome::where('is_active', true)->get() as $item) {
            try {
                $item->apply();
            } catch (\Exception $e) {
                Log::error("Failed to process recurring income {$item->id}: " . $e->getMessage());
            }
        }

        // 3. Process platform recurring salaries
        foreach (\App\Models\RecurringSalary::where('is_active', true)->get() as $item) {
            try {
                $item->apply();
            } catch (\Exception $e) {
                Log::error("Failed to process recurring salary {$item->id}: " . $e->getMessage());
            }
        }

        // 4. Process tenant erp_recurring_entries
        $today = Carbon::today()->toDateString();
        $dueEntries = \Modules\ERP\Models\RecurringEntry::withoutGlobalScopes()
            ->where('is_active', true)
            ->where('next_run_at', '<=', $today)
            ->where(function ($q) use ($today) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', $today);
            })
            ->get();

        foreach ($dueEntries as $entry) {
            try {
                $this->executeEntry($entry);
            } catch (\Exception $e) {
                Log::error("Failed to process recurring entry {$entry->id}: " . $e->getMessage());
            }
        }
    }

    /**
     * Execute a single recurring entry.
     */
    public function executeEntry(\Modules\ERP\Models\RecurringEntry $entry): void
    {
        $today = Carbon::today();
        
        $currentRun = Carbon::parse($entry->next_run_at);
        $nextRun = $currentRun->copy();
        
        switch ($entry->frequency) {
            case 'daily':
                $nextRun->addDay();
                break;
            case 'weekly':
                $nextRun->addWeek();
                break;
            case 'monthly':
                $nextRun->addMonth();
                break;
            case 'yearly':
                $nextRun->addYear();
                break;
        }
        
        if ($entry->frequency === 'weekly' && $entry->frequency_day !== null) {
            $nextRun->next((int)$entry->frequency_day);
        } elseif ($entry->frequency === 'monthly' && $entry->frequency_day !== null) {
            $nextRun->day((int)$entry->frequency_day);
            if ($nextRun->lt($currentRun)) {
                $nextRun->addMonth();
            }
        } elseif ($entry->frequency === 'yearly' && $entry->frequency_month !== null && $entry->frequency_day !== null) {
            $nextRun->month((int)$entry->frequency_month)->day((int)$entry->frequency_day);
            if ($nextRun->lt($currentRun)) {
                $nextRun->addYear();
            }
        }

        $entry->update([
            'last_run_at' => $today->toDateString(),
            'next_run_at' => $nextRun->toDateString(),
        ]);

        \Modules\ERP\Models\RecurringExecutionLog::create([
            'recurring_entry_id' => $entry->id,
            'executed_at' => $today,
            'amount' => $entry->amount,
            'currency_id' => $entry->currency_id,
            'business_amount' => $entry->business_amount,
            'business_currency_id' => $entry->business_currency_id,
            'exchange_rate' => $entry->exchange_rate,
            'exchange_rate_date' => $entry->exchange_rate_date,
            'status' => 'success',
            'note' => 'Automatically processed by scheduler.'
        ]);
    }
}
