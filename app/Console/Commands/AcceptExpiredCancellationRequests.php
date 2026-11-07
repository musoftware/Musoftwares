<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Marketplace\Services\OrderCancellation;

class AcceptExpiredCancellationRequests extends Command
{
    protected $signature = 'marketplace:accept-expired-cancellations';

    protected $description = 'Cancels and refunds marketplace orders whose cancellation request got no answer in time.';

    public function handle(OrderCancellation $cancellation): int
    {
        $this->info($cancellation->acceptExpiredRequests().' orders cancelled.');

        return self::SUCCESS;
    }
}
