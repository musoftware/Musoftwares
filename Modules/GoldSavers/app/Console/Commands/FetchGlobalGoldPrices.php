<?php

namespace Modules\GoldSavers\app\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;
use Modules\GoldSavers\app\Features\LivePrices\Services\GoldLivePriceService;
use Modules\GoldSavers\app\Features\LivePrices\Exceptions\GoldProviderException;

class FetchGlobalGoldPrices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gold:fetch-global';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch and update global gold prices for all tenants';

    /**
     * Execute the console command.
     */
    public function handle(GoldLivePriceService $service)
    {
        $this->info('Starting global gold prices fetch...');
        
        $tenants = Tenant::all();
        $successCount = 0;
        
        foreach ($tenants as $tenant) {
            try {
                $service->fetchAndUpdate($tenant->id, 'global');
                $this->line(" - Successfully updated global gold prices for tenant {$tenant->id}.");
                $successCount++;
            } catch (GoldProviderException $e) {
                $this->error(" - Provider error for tenant {$tenant->id} (global): " . $e->getMessage());
            } catch (\Exception $e) {
                $this->error(" - Failed to update global gold prices for tenant {$tenant->id}: " . $e->getMessage());
            }
        }
        
        $this->info("Global gold prices fetch completed. Success: {$successCount}/" . $tenants->count());
        return self::SUCCESS;
    }
}
