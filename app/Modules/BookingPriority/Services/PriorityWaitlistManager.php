<?php

namespace App\Modules\BookingPriority\Services;

class PriorityWaitlistManager
{
    public function addWithPriority(int $tenantId, int $bookingId, int $weight): void
    {
        // Logic to insert the booking into the waitlist taking into account its priority weight.
        // It bypasses normal waitlist entries if it has a higher weight.
    }
}
