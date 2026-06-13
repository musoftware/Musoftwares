<?php

namespace Modules\ERP\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Modules\ERP\Models\RecurringEntry;
use Modules\ERP\Models\RecurringExecutionLog;

/**
 * ERP-internal recurring entry processor.
 *
 * This service is fully self-contained within the ERP module.
 * It is invoked by the `erp:process-recurring` Artisan command,
 * which is registered in the main scheduler WITHOUT importing any ERP classes.
 *
 * This logic was previously in App\Services\RecurringService and has been
 * moved here to enforce full ERP module isolation.
 */
class RecurringService
{
    /**
     * Process all due ERP recurring entries for all tenants.
     * Runs across all tenants (withoutGlobalScopes) since this is a system-level scheduler job.
     */
    public function processDueEntries(): void
    {
        $today = Carbon::today()->toDateString();

        $dueEntries = RecurringEntry::withoutGlobalScopes()
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
                Log::error("[ERP RecurringService] Failed to process recurring entry {$entry->id} (tenant {$entry->tenant_id}): " . $e->getMessage());
            }
        }
    }

    /**
     * Execute a single recurring entry: update schedule dates and log execution.
     */
    public function executeEntry(RecurringEntry $entry): void
    {
        $today      = Carbon::today();
        $currentRun = Carbon::parse($entry->next_run_at);
        $nextRun    = $currentRun->copy();

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
            $nextRun->next((int) $entry->frequency_day);
        } elseif ($entry->frequency === 'monthly' && $entry->frequency_day !== null) {
            $nextRun->day((int) $entry->frequency_day);
            if ($nextRun->lt($currentRun)) {
                $nextRun->addMonth();
            }
        } elseif ($entry->frequency === 'yearly' && $entry->frequency_month !== null && $entry->frequency_day !== null) {
            $nextRun->month((int) $entry->frequency_month)->day((int) $entry->frequency_day);
            if ($nextRun->lt($currentRun)) {
                $nextRun->addYear();
            }
        }

        $entry->update([
            'last_run_at' => $today->toDateString(),
            'next_run_at' => $nextRun->toDateString(),
        ]);

        RecurringExecutionLog::create([
            'recurring_entry_id'  => $entry->id,
            'executed_at'         => $today,
            'amount'              => $entry->amount,
            'currency_id'         => $entry->currency_id,
            'business_amount'     => $entry->business_amount,
            'business_currency_id'=> $entry->business_currency_id,
            'exchange_rate'       => $entry->exchange_rate,
            'exchange_rate_date'  => $entry->exchange_rate_date,
            'status'              => 'success',
            'note'                => 'Automatically processed by ERP scheduler.',
        ]);
    }
}
