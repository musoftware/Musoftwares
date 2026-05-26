<?php

namespace App\Modules\CRMWhatsAppInbox\Jobs;

use App\Modules\CRMWhatsAppInbox\Services\WhatsAppSlaEngine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckSlaBreachesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    public $queue = 'whatsapp-sla';

    public function handle(WhatsAppSlaEngine $slaEngine): void
    {
        $slaEngine->checkAllBreaches();
    }
}
