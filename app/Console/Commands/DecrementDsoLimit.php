<?php

namespace App\Console\Commands;

use App\Models\AdminSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DecrementDsoLimit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:decrement-dso-limit';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Decrement the global DSO limit by 1 day on the 1st of every month down to a minimum of 1 day';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $enabled = AdminSettings::GetValue('enable_dso_decrement', '1') === '1';
        if (!$enabled) {
            $this->info("Global DSO limit decrement is disabled in admin settings. Skipping.");
            return;
        }

        $currentLimit = (int) AdminSettings::GetValue('global_dso_limit', 30);
        $this->info("Current global DSO limit before decrement: {$currentLimit} days.");

        if ($currentLimit > 1) {
            $newLimit = $currentLimit - 1;
            AdminSettings::SetValue('global_dso_limit', $newLimit);
            $this->info("Global DSO limit has been decremented to: {$newLimit} days.");

            Log::info("Global DSO limit decremented", [
                'old_limit' => $currentLimit,
                'new_limit' => $newLimit,
            ]);
        } else {
            $this->info("Global DSO limit is already at the minimum threshold of 1 day. No decrement performed.");
        }
    }
}
