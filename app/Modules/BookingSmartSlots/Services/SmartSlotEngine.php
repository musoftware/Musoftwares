<?php

namespace App\Modules\BookingSmartSlots\Services;

class SmartSlotEngine
{
    protected DynamicAvailabilityGenerator $generator;
    protected ResourceLoadBalancer $balancer;

    public function __construct(
        DynamicAvailabilityGenerator $generator,
        ResourceLoadBalancer $balancer
    ) {
        $this->generator = $generator;
        $this->balancer = $balancer;
    }

    public function generateAvailability(int $tenantId, int $branchId, string $date): array
    {
        // 1. Check load balancer to see if certain resources should be excluded due to high load
        $balancedResources = $this->balancer->getOptimalResources($tenantId, $branchId, $date);

        // 2. Generate dynamic slots for these resources
        return $this->generator->generate($tenantId, $branchId, $balancedResources, $date);
    }
}
