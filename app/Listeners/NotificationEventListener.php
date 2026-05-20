<?php

namespace App\Listeners;

use App\Events\InvoicePaid;
use App\Events\WithdrawalApproved;
use App\Notifications\InvoicePaidNotification;
use App\Notifications\WithdrawalApprovedNotification;
use Illuminate\Support\Facades\Notification;

class NotificationEventListener
{
    /**
     * Handle the event and map to the appropriate notification.
     */
    public function handle(object $event): void
    {
        match (true) {
            $event instanceof InvoicePaid => $this->handleInvoicePaid($event),
            $event instanceof WithdrawalApproved => $this->handleWithdrawalApproved($event),
            default => null,
        };
    }

    private function handleInvoicePaid(InvoicePaid $event): void
    {
        $invoice = $event->invoice;
        if (!$invoice) return;

        // Ensure we have a user to notify. E.g., the client's user record.
        $user = null;
        if (isset($invoice->client) && method_exists($invoice->client, 'user')) {
            $user = $invoice->client->user;
        }

        // Fallback for legacy setups where client might not have user_id, 
        // but perhaps the tenant owner is notified or a specific email is used.
        if ($user) {
            $user->notify(new InvoicePaidNotification($invoice));
        } else if (isset($invoice->client->email)) {
            Notification::route('mail', $invoice->client->email)
                ->notify(new InvoicePaidNotification($invoice));
        }
    }

    private function handleWithdrawalApproved(WithdrawalApproved $event): void
    {
        $withdrawal = $event->withdrawal;
        if (!$withdrawal) return;

        $user = null;
        if (isset($withdrawal->client) && isset($withdrawal->client->user)) {
            $user = $withdrawal->client->user;
        }

        if ($user) {
            $user->notify(new WithdrawalApprovedNotification($withdrawal));
        }
    }
}
