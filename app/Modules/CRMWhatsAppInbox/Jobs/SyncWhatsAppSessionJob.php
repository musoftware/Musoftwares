<?php

namespace App\Modules\CRMWhatsAppInbox\Jobs;

use App\Modules\CRMWhatsAppInbox\Services\WhatsAppSessionManager;
use Modules\CRM\Models\WhatsAppAccount;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncWhatsAppSessionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    public $queue = 'whatsapp-session';

    public function handle(WhatsAppSessionManager $sessionManager): void
    {
        // Check health of all connected accounts
        $accounts = WhatsAppAccount::withoutGlobalScopes()
            ->where('status', 'connected')
            ->cursor();

        foreach ($accounts as $account) {
            try {
                $health = $sessionManager->checkHealth($account);

                // Auto-reconnect if disconnected
                if ($health['status'] === 'disconnected' && $account->session_data) {
                    $sessionManager->reconnect($account);
                }
            } catch (\Exception $e) {
                \Log::warning("Session sync failed for account {$account->id}: {$e->getMessage()}");
            }
        }
    }
}
