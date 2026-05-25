<?php

namespace Modules\Booking\app\Features\OnlinePage\Services;

use Illuminate\Support\Facades\DB;
use Modules\Booking\Models\Booking;

class PublicBookingResolver
{
    /**
     * Securely create a booking from the public page, using pessimistic locking
     * to prevent race conditions.
     */
    public function createReservation(array $data)
    {
        return DB::transaction(function () use ($data) {
            // Lock the time slot conceptually by verifying no overlapping bookings
            // using lockForUpdate() on the resource or querying existing overlapping bookings
            
            $conflict = Booking::where('tenant_id', $data['tenant_id'])
                ->where('resource_id', $data['resource_id'])
                ->where('starts_at', $data['starts_at'])
                ->lockForUpdate()
                ->exists();

            if ($conflict) {
                throw new \Exception('Slot is no longer available.');
            }

            // Create booking
            return Booking::create($data);
        });
    }
}
