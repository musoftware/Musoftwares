<?php

namespace App\Console\Commands;

use App\Models\TenantUsage;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SaaSResetMetersCommand extends Command
{
    protected $signature = 'saas:reset-meters';

    protected $description = 'Scans tenant usages and resets those that have passed their reset_frequency interval.';

    public function handle()
    {
        $this->info('Starting SaaS Meter Reset daemon...');

        // We only process usages that shouldn't be 'never' reset
        $usages = TenantUsage::where('reset_frequency', '!=', 'never')->get();

        $resetCount = 0;

        foreach ($usages as $usage) {
            if ($usage->needsReset()) {
                $usage->used_amount = 0;
                $usage->last_reset_at = Carbon::now();
                $usage->save();

                $resetCount++;
            }
        }

        $this->info("Reset {$resetCount} usage meters successfully.");
    }
}
