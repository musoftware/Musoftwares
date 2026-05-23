<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class RecurringService
{
    /**
     * Process all due recurring entries.
     */
    public function processDueEntries(): void
    {
        Log::info('RecurringService: processDueEntries not fully implemented after Core migration.');
    }
}
