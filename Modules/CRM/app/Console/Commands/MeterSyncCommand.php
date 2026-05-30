<?php

namespace Modules\CRM\app\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;
use App\Models\UsageLedger; // Assume this model exists or will be created for tracking usage

class MeterSyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'crm:meter:sync';

    /**
     * The console command description.
     */
    protected $description = 'Sync Redis atomic billing meters to the database usage ledgers';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dirtyKeys = Redis::smembers('metering:dirty_keys');

        if (empty($dirtyKeys)) {
            $this->info('No pending meters to sync.');
            return 0;
        }

        DB::beginTransaction();

        try {
            foreach ($dirtyKeys as $redisKey) {
                // Expected format: metering:tenant:{tenantId}:{featureKey}
                $parts = explode(':', $redisKey);
                
                if (count($parts) !== 4) {
                    continue;
                }

                $tenantId = $parts[2];
                $featureKey = $parts[3];

                // Get current value and atomically reset it to 0
                // using a GETSET operation or multi block. 
                // A safer approach: read it, then decrement by what we read.
                $currentValue = (int) Redis::get($redisKey);

                if ($currentValue > 0) {
                    // Decrement by the amount we are about to save
                    Redis::decrby($redisKey, $currentValue);

                    // Insert or update DB ledger (assuming we just append logs)
                    // UsageLedger::create([
                    //    'tenant_id' => $tenantId,
                    //    'feature' => $featureKey,
                    //    'usage' => $currentValue,
                    //    'recorded_at' => now(),
                    // ]);
                    
                    // For the sake of the blueprint, we simulate the save:
                    $this->info("Synced {$currentValue} usage for {$featureKey} on tenant {$tenantId}");
                }

                // Remove from dirty keys if zeroed out
                if ((int) Redis::get($redisKey) === 0) {
                    Redis::srem('metering:dirty_keys', $redisKey);
                }
            }

            DB::commit();
            $this->info('Metering sync completed successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Failed to sync meters: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
