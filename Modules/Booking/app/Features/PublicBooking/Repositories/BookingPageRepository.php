<?php

namespace Modules\Booking\app\Features\PublicBooking\Repositories;

use Modules\Booking\app\Features\PublicBooking\Models\BookingPageSetting;

class BookingPageRepository
{
    /**
     * Get the booking page settings for the current tenant.
     * Uses the global tenant scope implicitly if auth'd.
     */
    public function getSettings()
    {
        $tenantId = (app()->bound('currentTenant') ? app('currentTenant')->id : auth()->id());
        
        return BookingPageSetting::firstOrCreate(
            ['tenant_id' => $tenantId],
            [
                'slug' => 'booking-' . \Illuminate\Support\Str::random(6),
                'title' => 'Book an Appointment',
                'is_active' => false,
            ]
        );
    }

    /**
     * Get booking page by slug (Public access, no global tenant scope needed).
     */
    public function findBySlug(string $slug): ?BookingPageSetting
    {
        return BookingPageSetting::withoutGlobalScope('tenant')
            ->where('slug', $slug)
            ->first();
    }
}
