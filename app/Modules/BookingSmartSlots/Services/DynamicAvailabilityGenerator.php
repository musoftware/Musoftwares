<?php

namespace App\Modules\BookingSmartSlots\Services;

class DynamicAvailabilityGenerator
{
    protected GapOptimizationService $gapService;
    protected AdaptiveSlotAllocator $allocator;

    public function __construct(GapOptimizationService $gapService, AdaptiveSlotAllocator $allocator)
    {
        $this->gapService = $gapService;
        $this->allocator = $allocator;
    }

    public function generate(int $tenantId, int $branchId, array $resourceIds, string $date): array
    {
        // Generate base slots based on working hours
        $baseSlots = []; // Mock fetching base slots

        // Pass through Gap Optimization
        $optimizedSlots = $this->gapService->optimize($baseSlots);

        // Allocate adaptive durations
        return $this->allocator->allocate($optimizedSlots);
    }
}
