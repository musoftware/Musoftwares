<?php

namespace App\Observers;

use App\Models\Ticket;
use App\Services\LoyaltyService;
use App\Services\WinbackService;

class TicketLoyaltyObserver
{
    public function __construct(
        private readonly LoyaltyService $loyaltyService,
        private readonly WinbackService $winbackService,
    ) {}

    /**
     * Award points only when a ticket is opened via the self-service portal.
     * Tickets opened by agents or other channels earn zero points.
     */
    public function created(Ticket $ticket): void
    {
        $user = $ticket->user ?? null;

        if ($user === null || ! $ticket->is_self_service) {
            return;
        }

        $this->loyaltyService->awardPointsForEvent(
            user: $user,
            eventType: 'ticket_portal_created',
            reference: $ticket,
            context: ['channel' => 'web_portal']
        );

        $this->winbackService->touchActivity($user, 'ticket_opened');
    }

    /**
     * Award bonus points when a ticket is resolved with a positive rating (rate >= 4).
     */
    public function updated(Ticket $ticket): void
    {
        if (! $ticket->wasChanged('ticket_status')) {
            return;
        }

        if ($ticket->ticket_status !== 'closed') {
            return;
        }

        $user = $ticket->user ?? null;

        if ($user === null || ! $ticket->is_self_service) {
            return;
        }

        // Only award resolution points if the ticket has a positive rating
        $hasPositiveRating = $ticket->rate !== null && (int) $ticket->rate >= 4;

        if ($hasPositiveRating) {
            $this->loyaltyService->awardPointsForEvent(
                user: $user,
                eventType: 'ticket_resolved_positive',
                reference: $ticket,
                context: ['channel' => 'web_portal', 'rate' => $ticket->rate]
            );
        }

        $this->winbackService->touchActivity($user, 'ticket_closed');
    }
}
