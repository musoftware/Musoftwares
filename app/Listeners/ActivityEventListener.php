<?php

namespace App\Listeners;

use App\Services\ActivityService;
use App\Events\InvoicePaid;
use App\Events\WalletCredited;
use App\Events\WalletDebited;
use App\Events\WithdrawalRequested;
use App\Events\WithdrawalApproved;
use App\Events\MarketplaceOrderPlaced;
use App\Events\MarketplaceOrderCompleted;
use App\Events\ProposalAccepted;
use App\Events\ReferralCommissionEarned;

/**
 * ActivityEventListener — translates Laravel events into ActivityEvent records.
 *
 * This is the single wiring point between domain events and the activity engine.
 * Add new event → activity mappings here as the ecosystem grows.
 */
class ActivityEventListener
{
    public function handle(object $event): void
    {
        try {
        match (true) {

            $event instanceof InvoicePaid => ActivityService::log(
                event:       'invoice.paid',
                description: "Invoice was marked as paid.",
                subject:     $event->invoice ?? null,
                workspace:   'erp',
                properties:  $this->invoiceProps($event->invoice ?? null),
            ),

            $event instanceof WalletCredited => ActivityService::log(
                event:       'wallet.credited',
                description: "Wallet credited with " . ($event->amount ?? '') . " " . ($event->currency ?? ''),
                workspace:   'erp',
                properties:  ['amount' => $event->amount ?? null, 'currency' => $event->currency ?? null],
            ),

            $event instanceof WalletDebited => ActivityService::log(
                event:       'wallet.debited',
                description: "Wallet debited " . ($event->amount ?? '') . " " . ($event->currency ?? ''),
                workspace:   'erp',
                properties:  ['amount' => $event->amount ?? null, 'currency' => $event->currency ?? null],
            ),

            $event instanceof WithdrawalRequested => ActivityService::log(
                event:       'withdrawal.requested',
                description: "A withdrawal request was submitted.",
                subject:     $event->withdrawal ?? null,
                workspace:   'erp',
                properties:  ['amount' => $event->withdrawal->amount ?? null],
            ),

            $event instanceof WithdrawalApproved => ActivityService::log(
                event:       'withdrawal.approved',
                description: "A withdrawal was approved.",
                subject:     $event->withdrawal ?? null,
                workspace:   'erp',
                properties:  ['amount' => $event->withdrawal->amount ?? null],
            ),

            $event instanceof MarketplaceOrderPlaced => ActivityService::log(
                event:       'order.placed',
                description: "A new marketplace order was placed.",
                subject:     $event->order ?? null,
                workspace:   'marketplace',
            ),

            $event instanceof MarketplaceOrderCompleted => ActivityService::log(
                event:       'order.completed',
                description: "A marketplace order was completed.",
                subject:     $event->order ?? null,
                workspace:   'marketplace',
            ),

            $event instanceof ProposalAccepted => ActivityService::log(
                event:       'proposal.accepted',
                description: "A freelance proposal was accepted.",
                subject:     $event->proposal ?? null,
                workspace:   'freelance',
            ),

            $event instanceof ReferralCommissionEarned => ActivityService::log(
                event:       'referral.commission_earned',
                description: "Referral commission earned: " . ($event->amount ?? '') . " " . ($event->currency ?? ''),
                workspace:   'erp',
                properties:  ['amount' => $event->amount ?? null, 'currency' => $event->currency ?? null],
            ),

            default => null,
        };
        } catch (\Throwable $e) {
            // Activity logging must never break the main event flow.
            // Swallow exceptions silently to allow subsequent listeners (e.g. NotificationEventListener) to run.
            logger()->warning('ActivityEventListener silently failed: ' . $e->getMessage(), ['exception' => $e]);
        }
    }

    private function invoiceProps($invoice): array
    {
        if (!$invoice) return [];
        return array_filter([
            'invoice_id' => $invoice->id ?? null,
            'amount'     => $invoice->total ?? $invoice->amount ?? null,
            'currency'   => $invoice->currency_id ?? null,
            'client'     => $invoice->client->name ?? null,
        ]);
    }
}
