<?php

namespace App\Console\Commands;

use App\Models\ClientSystem;
use App\Services\ClientSystems\SystemBilling;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/** Makes the monthly bill of every client system whose bill date has come. One failing system never stops the rest. */
class BillClientSystems extends Command
{
    protected $signature = 'client-systems:bill';

    protected $description = 'Create the monthly bills of licensed client systems';

    public function handle(SystemBilling $billing): int
    {
        $made = 0;
        $failed = 0;
        ClientSystem::whereNotNull('next_bill_at')->where('next_bill_at', '<=', now())->each(function (ClientSystem $system) use ($billing, &$made, &$failed) {
            try {
                $made += $billing->billDue($system, now());
            } catch (\Throwable $e) {
                $failed++;
                Log::error('ClientSystems: monthly bill failed, retried next run', ['client_system_id' => $system->id, 'error' => $e->getMessage()]);
            }
        });
        $this->info("Bills made: {$made}, systems failed: {$failed}");

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
