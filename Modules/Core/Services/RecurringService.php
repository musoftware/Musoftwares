<?php

namespace Modules\Core\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\ERP\Models\RecurringEntry;

class RecurringService
{
    public function processDueEntries(): void
    {
        $today = now()->toDateString();

        // We use DB to bypass any global scopes if needed, or query the model directly.
        // Assuming we want to process all due entries globally.
        $dueEntries = RecurringEntry::withoutGlobalScopes()
            ->where('is_active', true)
            ->where('next_run_at', '<=', $today)
            ->where(function ($query) use ($today) {
                $query->whereNull('ends_at')
                      ->orWhere('ends_at', '>=', $today);
            })
            ->get();

        foreach ($dueEntries as $entry) {
            try {
                DB::transaction(function () use ($entry) {
                    $this->executeEntry($entry);

                    $nextDate = $this->calculateNextRunAt($entry);
                    $entry->next_run_at = $nextDate;
                    $entry->last_run_at = now();

                    if ($entry->ends_at && $nextDate > $entry->ends_at) {
                        $entry->is_active = false;
                    }

                    $entry->save();
                });
            } catch (\Exception $e) {
                Log::error("Failed to process recurring entry {$entry->id}: " . $e->getMessage());
            }
        }
    }

    public function calculateNextRunAt(RecurringEntry $entry): Carbon
    {
        $currentDate = Carbon::parse($entry->next_run_at);

        switch ($entry->frequency) {
            case 'daily':
                return $currentDate->addDay();
            case 'weekly':
                return $currentDate->addWeek();
            case 'monthly':
                return $currentDate->addMonth();
            case 'yearly':
                return $currentDate->addYear();
            default:
                // fallback to monthly if unknown
                return $currentDate->addMonth();
        }
    }

    public function executeEntry(RecurringEntry $entry): void
    {
        DB::table('recurring_execution_logs')->insert([
            'recurring_entry_id' => $entry->id,
            'executed_at' => now(),
            'amount' => $entry->amount,
            'amount_currency' => $entry->amount_currency,
            'business_amount' => $entry->business_amount,
            'business_currency' => $entry->business_currency,
            'exchange_rate' => $entry->exchange_rate,
            'exchange_rate_date' => $entry->exchange_rate_date,
            'status' => 'success',
            'note' => 'Executed successfully.',
            'created_at' => now(),
        ]);
    }
}
