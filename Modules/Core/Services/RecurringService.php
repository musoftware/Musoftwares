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
            ->where('next_date', '<=', $today)
            ->where(function ($query) use ($today) {
                $query->whereNull('end_date')
                      ->orWhere('end_date', '>=', $today);
            })
            ->get();

        foreach ($dueEntries as $entry) {
            try {
                DB::transaction(function () use ($entry) {
                    $this->executeEntry($entry);

                    $nextDate = $this->calculateNextRunAt($entry);
                    $entry->next_date = $nextDate;

                    if ($entry->end_date && $nextDate > $entry->end_date) {
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
        $currentDate = Carbon::parse($entry->next_date);

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
        // Execute the recurring entry. E.g., create an expense transaction or income.
        // Based on the migration, there's `expense_transactions`. We can log income somewhere too.
        if ($entry->type === 'expense') {
            DB::table('expense_transactions')->insert([
                'tenant_id' => $entry->tenant_id,
                'description' => $entry->description . ' (Recurring)',
                'amount' => $entry->amount,
                'currency_code' => $entry->currency_code,
                'date' => $entry->next_date, // create it for the due date
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } elseif ($entry->type === 'income') {
            // Placeholder for income, e.g., create an invoice or ledger entry
            Log::info("Recurring income entry executed for tenant {$entry->tenant_id}");
        }
    }
}
