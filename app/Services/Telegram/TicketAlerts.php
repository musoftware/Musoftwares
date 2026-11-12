<?php

namespace App\Services\Telegram;

use App\Models\Ticket;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use Throwable;

/** A support ticket moved on (answered, solved, closed): the customer hears it in Telegram with the ticket one tap away. */
class TicketAlerts
{
    public function __construct(private readonly CustomerAlerts $alerts) {}

    public function statusChanged(Ticket $ticket): void
    {
        $user = $ticket->user;
        if (! $this->alerts->reaches($user)) {
            return;
        }
        $previous = app()->getLocale();
        app()->setLocale(CustomerAlerts::localeOf($user));
        try {
            $key = 'telegram.ticket_status.'.$ticket->ticket_status;
            $this->alerts->send($user, __('telegram.ticket_changed', [
                'id' => $ticket->id,
                'subject' => mb_substr((string) $ticket->ticket_subject, 0, 60),
                'status' => Lang::has($key) ? __($key) : (string) $ticket->ticket_status,
            ]), [[__('telegram.ticket_open', ['id' => $ticket->id]), route('tickets.show', $ticket->id)]]);
        } catch (Throwable $error) {
            // a ticket is saved whatever happens to its alert
            Log::warning('Telegram ticket alert failed', ['ticket_id' => $ticket->id, 'error' => $error->getMessage()]);
        } finally {
            app()->setLocale($previous);
        }
    }
}
