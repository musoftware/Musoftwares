<?php

namespace App\Modules\BookingSmartSlots\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Modules\BookingSmartSlots\Services\SlotUtilizationAnalyzer;
use App\Modules\BookingSmartSlots\Models\BookingSmartSlotSnapshot;

class AggregateUtilizationMetricsJob implements ShouldQueue
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

    public function handle(SlotUtilizationAnalyzer $analyzer): void
    {
        $score = $analyzer->calculateFragmentationScore($this->tenantId, $this->branchId, $this->date);
        
        BookingSmartSlotSnapshot::updateOrCreate(
            ['tenant_id' => $this->tenantId, 'branch_id' => $this->branchId, 'date' => $this->date],
            ['fragmentation_score' => $score]
        );
    }
}
