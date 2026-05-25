<?php

namespace Modules\Booking\Features\Scheduling;

class AvailabilityResolver
{
    /**
     * Finds resources that are available for a given service and date range.
     * Useful for finding "any available doctor" for a service.
     *
     * @param int $serviceId
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function resolveAvailableResources(int $serviceId, string $startDate, string $endDate): array
    {
        $service = \Modules\Booking\Features\Services\BookingService::find($serviceId);
        if (!$service) {
            return [];
        }

        // 1. Get all active resources in the current tenant.
        // In a real scenario, there might be a pivot table linking resources to services they provide.
        // For now, we fetch all active resources.
        $resources = \Modules\Booking\Features\Resources\BookingResource::where('is_active', true)->get();

        $availableResources = [];
        $slotGenerator = new SlotGenerator();

        // 2. For each resource, run SlotGenerator to check if there is at least one slot
        foreach ($resources as $resource) {
            $slots = $slotGenerator->generate($resource->id, $startDate, $endDate, $service->duration);

            if (!empty($slots)) {
                $availableResources[] = [
                    'resource' => $resource,
                    'available_slots_count' => count($slots),
                    'first_available_slot' => $slots[0]['start_at']
                ];
            }
        }

        return $availableResources;
    }
}
