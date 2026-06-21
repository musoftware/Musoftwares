<?php

namespace Modules\ERP\Listeners;

use Modules\Booking\Events\BookingConfirmed;
use Modules\ERP\Models\Tenant;
use Modules\ERP\Models\TenantClient;
use Illuminate\Support\Facades\Log;

/**
 * ERP listener for Booking's BookingConfirmed event.
 *
 * This keeps the ERP module as a standalone SaaS — the Booking
 * module fires an event and forgets. The ERP module independently
 * decides to sync the guest as a client if the host has an ERP workspace.
 *
 * This listener is ONLY registered when the ERP module is loaded.
 */
class SyncBookingClientToErpListener
{
    /**
     * Handle the event.
     */
    public function handle(BookingConfirmed $event): void
    {
        try {
            $tenant = Tenant::where('user_id', $event->hostUserId)->first();

            if (! $tenant) {
                // Host has no ERP workspace — nothing to sync. This is fine.
                return;
            }

            TenantClient::firstOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'email'     => $event->guestEmail,
                ],
                [
                    'name'        => $event->guestName,
                    'phone'       => $event->guestPhone,
                    'currency_id' => $event->currencyId ?? null,
                ]
            );
        } catch (\Throwable $e) {
            // Never break the booking flow due to ERP sync failure.
            Log::warning('[ERP] Failed to sync booking guest to ERP client: ' . $e->getMessage(), [
                'host_user_id' => $event->hostUserId,
                'guest_email'  => $event->guestEmail,
            ]);
        }
    }
}
