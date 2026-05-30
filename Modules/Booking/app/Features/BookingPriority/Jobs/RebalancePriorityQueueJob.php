<?php

namespace Modules\Booking\app\Features\BookingPriority\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Booking\app\Features\BookingPriority\Services\PriorityQueueResolver;

class RebalancePriorityQueueJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tenantId;
    public int $branchId;
    public string $date;

    public function __construct(int $tenantId, int $branchId, string $date)
    {
        $this->tenantId = $tenantId;
        $this->branchId = $branchId;
        $this->date = $date;
    }

    public function handle(PriorityQueueResolver $resolver): void
    {
        $resolver->rebalanceQueue($this->tenantId, $this->branchId, $this->date);
    }
}
