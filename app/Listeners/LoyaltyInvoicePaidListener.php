<?php

namespace App\Listeners;

use App\Events\InvoicePaid;
use App\Services\LoyaltyService;
use App\Services\WinbackService;
use Illuminate\Contracts\Queue\ShouldQueue;

class LoyaltyInvoicePaidListener implements ShouldQueue
{
    public string $queue = 'default';

    public function __construct(
        private readonly LoyaltyService $loyaltyService,
        private readonly WinbackService $winbackService,
    ) {}

    public function handle(InvoicePaid $event): void
    {
        $invoice = $event->invoice;
        $user = $invoice->user ?? $invoice->client ?? null;

        if ($user === null) {
            return;
        }

        // Award points with early-payment multiplier applied via the rules engine
        $this->loyaltyService->awardPointsForEvent(
            user: $user,
            eventType: 'invoice_payment',
            reference: $invoice,
            context: [
                'invoice'   => $invoice,
                'channel'   => 'system',
                'invoice_id' => $invoice->id,
                'amount'    => $invoice->paid ?? $invoice->total(),
            ]
        );

        // Mark user as active — cancels any open win-back sequence immediately
        $this->winbackService->touchActivity($user, 'invoice_paid');
    }
}
