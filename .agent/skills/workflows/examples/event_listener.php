<?php

namespace App\Listeners;

use App\Events\InvoicePaid;
use App\Services\LicenseService;
use App\Services\ActivityStreamService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class UnlockPluginLicense implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private LicenseService $licenseService,
        private ActivityStreamService $activityService
    ) {}

    /**
     * Handle the event.
     * This is executed on the queue, ensuring the web request isn't blocked.
     */
    public function handle(InvoicePaid $event): void
    {
        $invoice = $event->invoice;
        
        // Only process if the invoice contains a plugin product
        if (!$invoice->hasProductType('plugin')) {
            return;
        }

        try {
            foreach ($invoice->items as $item) {
                if ($item->isPlugin()) {
                    $this->licenseService->issueLicense($invoice->client_id, $item->product_id);
                    
                    $this->activityService->log(
                        $invoice->client_id, 
                        "License for {$item->name} automatically issued following invoice payment."
                    );
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to issue license after invoice payment', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage()
            ]);
            
            // Release back to queue with delay
            $this->release(60);
        }
    }
}
