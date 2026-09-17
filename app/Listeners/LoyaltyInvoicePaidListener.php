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

        // Award referral points to the referrer if this is the user's first paid invoice
        if (! empty($user->ref_user_id)) {
            $referrer = \App\Models\User::find($user->ref_user_id);
            if ($referrer !== null) {
                $hasPriorPaidInvoices = $user->invoices()
                    ->where('status', 'paid')
                    ->where('id', '!=', $invoice->id)
                    ->exists();

                if (! $hasPriorPaidInvoices) {
                    $this->loyaltyService->awardPointsForEvent(
                        user: $referrer,
                        eventType: 'referral_first_payment',
                        reference: $invoice,
                        context: [
                            'referred_user_id'   => $user->id,
                            'referred_user_name' => $user->name,
                            'invoice_id'         => $invoice->id,
                            'channel'            => 'referral',
                        ]
                    );
                }
            }
        }

        // Mark user as active — cancels any open win-back sequence immediately
        $this->winbackService->touchActivity($user, 'invoice_paid');
    }
}
