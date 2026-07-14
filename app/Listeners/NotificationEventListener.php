<?php

namespace App\Listeners;

use App\Events\AmountReceived;
use App\Events\ContractSigned;
use App\Events\InvoiceCancelled;
use App\Events\InvoiceCreated;
use App\Events\InvoiceItemAdded;
use App\Events\InvoicePaid;
use App\Events\SerialUserDeviceStatusChanged;
use App\Events\WithdrawalApproved;
use App\Events\WithdrawalRequested;
use App\Models\User;
use App\Notifications\AmountReceivedNotification;
use App\Notifications\ContractSignedNotification;
use App\Notifications\InvoiceCancelledNotification;
use App\Notifications\InvoiceCreatedNotification;
use App\Notifications\InvoiceItemAddedNotification;
use App\Notifications\InvoicePaidNotification;
use App\Notifications\SerialUserDeviceStatusChangedNotification;
use App\Notifications\WithdrawalApprovedNotification;
use App\Notifications\WithdrawalRequestedNotification;
use Illuminate\Support\Facades\Log;
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
            $event instanceof InvoiceCreated => $this->handleInvoiceCreated($event),
            $event instanceof InvoiceItemAdded => $this->handleInvoiceItemAdded($event),
            $event instanceof InvoiceCancelled => $this->handleInvoiceCancelled($event),
            $event instanceof AmountReceived => $this->handleAmountReceived($event),
            $event instanceof WithdrawalRequested => $this->handleWithdrawalRequested($event),
            $event instanceof WithdrawalApproved => $this->handleWithdrawalApproved($event),
            $event instanceof ContractSigned => $this->handleContractSigned($event),
            $event instanceof SerialUserDeviceStatusChanged => $this->handleSerialUserDeviceStatusChanged($event),
            default => null,
        };
    }

    /**
     * Resolve an invoice's client User (the recipient).
     *
     * Note: Invoice::client() returns belongsTo(User::class, 'user_id'), so
     * $invoice->client IS the User already — there is no nested ->user.
     */
    protected function resolveInvoiceRecipient($invoice)
    {
        if (! $invoice) {
            return null;
        }

        $client = $invoice->client ?? null;

        // Modern shape: client relationship returns the User directly.
        if ($client instanceof User) {
            return $client;
        }

        // Legacy fallback: a nested Client model that exposes a user().
        if ($client && method_exists($client, 'user')) {
            return $client->user;
        }

        return null;
    }

    /**
     * Notify a recipient, preferring the User (FCM + mail) and falling back to
     * a mail-only route when only an email is available.
     */
    protected function notifyRecipient($user, ?string $email, $notification): void
    {
        if ($user) {
            $user->notify($notification);

            return;
        }

        if ($email) {
            $userByEmail = \App\Models\User::where('email', $email)->first();
            if ($userByEmail && ! ($userByEmail->enable_notifications ?? true)) {
                Log::info('Notification skipped: notifications are disabled for this recipient email.', [
                    'email' => $email,
                    'notification' => get_class($notification),
                ]);
                return;
            }

            Notification::route('mail', $email)->notify($notification);

            return;
        }

        Log::info('Notification skipped: no resolvable recipient.', [
            'notification' => get_class($notification),
        ]);
    }

    private function handleInvoicePaid(InvoicePaid $event): void
    {
        $invoice = $event->invoice;
        if (! $invoice) {
            return;
        }

        $user = $this->resolveInvoiceRecipient($invoice);
        $email = $invoice->client->email ?? null;

        // Single delivery path to avoid duplicate mail/FCM (previously both
        // user-notify and mail-route fired when a client existed).
        $this->notifyRecipient($user, $email, new InvoicePaidNotification($invoice));
    }

    private function handleInvoiceCreated(InvoiceCreated $event): void
    {
        $invoice = $event->invoice;
        if (! $invoice) {
            return;
        }

        $user = $this->resolveInvoiceRecipient($invoice);
        $email = $invoice->client->email ?? null;

        $this->notifyRecipient($user, $email, new InvoiceCreatedNotification($invoice));
    }

    private function handleInvoiceItemAdded(InvoiceItemAdded $event): void
    {
        $invoice = $event->invoice;
        if (! $invoice || ! $event->item) {
            return;
        }

        $user = $this->resolveInvoiceRecipient($invoice);
        $email = $invoice->client->email ?? null;

        $this->notifyRecipient($user, $email, new InvoiceItemAddedNotification($invoice, $event->item));
    }

    private function handleInvoiceCancelled(InvoiceCancelled $event): void
    {
        $invoice = $event->invoice;
        if (! $invoice) {
            return;
        }

        $user = $this->resolveInvoiceRecipient($invoice);
        $email = $invoice->client->email ?? null;

        $this->notifyRecipient($user, $email, new InvoiceCancelledNotification($invoice));
    }

    private function handleAmountReceived(AmountReceived $event): void
    {
        // AmountReceived carries the client User instance directly.
        $client = $event->client;

        $user = $client instanceof User ? $client : null;
        $email = is_object($client) ? ($client->email ?? null) : null;

        $this->notifyRecipient(
            $user,
            $email,
            new AmountReceivedNotification($event->amount, $event->currencyId)
        );
    }

    private function resolveWithdrawalRecipient($withdrawal)
    {
        if (! $withdrawal) {
            return null;
        }

        // Common shapes: ->client->user, ->user, or ->client being the User.
        if (method_exists($withdrawal, 'client')) {
            $client = $withdrawal->client;
            if ($client instanceof User) {
                return $client;
            }
            if ($client && method_exists($client, 'user')) {
                return $client->user;
            }
        }

        if (method_exists($withdrawal, 'user')) {
            return $withdrawal->user;
        }

        return null;
    }

    private function handleWithdrawalRequested(WithdrawalRequested $event): void
    {
        $withdrawal = $event->withdrawal;
        if (! $withdrawal) {
            return;
        }

        $user = $this->resolveWithdrawalRecipient($withdrawal);
        $email = is_object($withdrawal) ? ($withdrawal->client->email ?? $withdrawal->user->email ?? null) : null;

        $this->notifyRecipient($user, $email, new WithdrawalRequestedNotification($withdrawal));
    }

    private function handleWithdrawalApproved(WithdrawalApproved $event): void
    {
        $withdrawal = $event->withdrawal;
        if (! $withdrawal) {
            return;
        }

        $user = $this->resolveWithdrawalRecipient($withdrawal);
        $email = is_object($withdrawal) ? ($withdrawal->client->email ?? $withdrawal->user->email ?? null) : null;

        $this->notifyRecipient($user, $email, new WithdrawalApprovedNotification($withdrawal));
    }

    private function handleContractSigned(ContractSigned $event): void
    {
        $contract = $event->contract;
        if (! $contract) {
            return;
        }

        // Contract::user() returns belongsTo(User::class) — the client signing.
        $user = method_exists($contract, 'user') ? $contract->user : null;
        $email = is_object($contract) ? ($contract->user->email ?? null) : null;

        $this->notifyRecipient($user, $email, new ContractSignedNotification($contract));
    }

    private function handleSerialUserDeviceStatusChanged(SerialUserDeviceStatusChanged $event): void
    {
        $device = $event->serialUserDevice;
        if (! $device) {
            return;
        }

        $user = method_exists($device, 'user') ? $device->user : null;
        if (! $user) {
            return;
        }

        $user->notify(new SerialUserDeviceStatusChangedNotification($device, $event->oldStatus, $event->newStatus));
    }
}
