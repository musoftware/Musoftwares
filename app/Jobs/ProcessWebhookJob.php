<?php

namespace App\Jobs;

use App\Helpers\KashierHelper;
use App\Helpers\KashierSignedAmount;
use App\Models\IncomingWebhook;
use App\Models\Invoice;
use App\Models\KashierCheckout;
use App\Models\PaymentLink;
use App\Models\User;
use App\Services\BalanceService;
use App\Services\KashierCheckoutFulfillment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Booking\Models\Booking;

class ProcessWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $webhook;

    /**
     * Create a new job instance.
     */
    public function __construct(IncomingWebhook $webhook)
    {
        $this->webhook = $webhook;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // 1. Check if already processed
        if ($this->webhook->status === 'processed') {
            Log::info("Webhook ID {$this->webhook->id} already processed. Skipping.");

            return;
        }

        try {
            // 2. Validate Security Payload (Signature validation)
            if (! $this->validateSignature($this->webhook->source, $this->webhook->payload, $this->webhook->headers)) {
                throw new \Exception("Invalid webhook signature for source: {$this->webhook->source}");
            }

            // 3. Process based on source
            // Only Kashier has a verifier, so validateSignature() already rejected every other source.
            $this->processKashier($this->webhook->payload);

            // 4. Mark as processed
            $this->webhook->update([
                'status' => 'processed',
                'processed_at' => now(),
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to process webhook ID {$this->webhook->id}: ".$e->getMessage());

            $this->webhook->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            // Rethrow to trigger queue retry (exponential backoff handled by Laravel queue settings)
            throw $e;
        }
    }

    /**
     * Validate the webhook signature.
     */
    private function validateSignature($source, $payload, $headers): bool
    {
        if ($source === 'kashier') {
            $signature = $headers['x-kashier-signature'][0] ?? ($headers['X-Kashier-Signature'][0] ?? null);

            return KashierHelper::validatePayload($payload, $signature);
        }

        // No verifier is implemented for any other source (whatsapp, stripe, ...): never trust them.
        Log::warning('Webhook rejected: no signature verifier for source.', [
            'webhook_id' => $this->webhook->id,
            'source' => $source,
        ]);

        return false;
    }

    private function processKashier($payload)
    {
        Log::info('Processing Kashier webhook', ['payload' => $payload]);
        if (! isset($payload['data']) || $payload['data']['status'] !== 'SUCCESS') {
            return;
        }

        $data = $payload['data'];
        $metaData = $data['metaData'] ?? [];
        if (is_string($metaData)) {
            $metaData = json_decode($metaData, true) ?: [];
        }

        $source = $metaData['source'] ?? null;
        $merchantOrderId = $data['merchantOrderId'] ?? '';

        // Fallback source & ID deduction from merchantOrderId if metaData was omitted by gateway
        if (! $source && $merchantOrderId) {
            if (str_starts_with($merchantOrderId, 'plnk_')) {
                $source = 'payment-link';
                if (! isset($metaData['payment_link_id']) && preg_match('/^plnk_(\d+)_/', $merchantOrderId, $matches)) {
                    $metaData['payment_link_id'] = (int) $matches[1];
                }
            } elseif (str_starts_with($merchantOrderId, 'u_inv_')) {
                $source = 'user-invoice-payment';
                if (! isset($metaData['invoice_id']) && preg_match('/^u_inv_(\d+)_/', $merchantOrderId, $matches)) {
                    $metaData['invoice_id'] = (int) $matches[1];
                }
            } elseif (str_starts_with($merchantOrderId, 'inv_')) {
                $source = 'guest-invoice-payment';
                if (! isset($metaData['invoice_id']) && preg_match('/^inv_(\d+)_/', $merchantOrderId, $matches)) {
                    $metaData['invoice_id'] = (int) $matches[1];
                }
            }
        }

        $userId = $metaData['user_id'] ?? null;
        if (! $userId && $merchantOrderId) {
            if (preg_match('/-(\d+)$/', $merchantOrderId, $uMatches)) {
                $userId = (int) $uMatches[1];
            }
        }

        $trxId = $data['transactionId'] ?? null;

        // Only the signed charge (data.amount + data.currency) is trusted; metaData.original_amount is payer-editable.
        $paid = KashierSignedAmount::fromWebhookData($data);

        if (! $source || ! $trxId || ! $paid) {
            throw new \Exception('Invalid Kashier webhook payload structure.');
        }

        // Route to the appropriate service logic based on source
        switch ($source) {
            case 'balance-recharge':
                $this->handleBalanceRecharge($userId, (string) $trxId, $paid, $data);
                break;
            case KashierCheckout::PURPOSE_SUBSCRIPTION:
            case KashierCheckout::PURPOSE_POINTS:
                $this->handleCheckoutPurchase($data, $source);
                break;
            case 'booking-purchase':
                $this->handleBookingPurchase($userId, $trxId, $paid->amount, $metaData);
                break;
            case 'guest-invoice-payment':
            case 'user-invoice-payment':
                $this->handleInvoicePayment($trxId, $paid, $metaData);
                break;
            case 'payment-link':
                $this->handlePaymentLink($trxId, $paid->amount, $metaData, $paid);
                break;
            default:
                Log::warning("Unknown Kashier webhook source: {$source}");
        }
    }

    private function findUserOrFail($userId, string $trxId): User
    {
        $user = $userId ? User::find($userId) : null;
        if (! $user) {
            throw new \Exception("Kashier webhook {$trxId} references unknown user ".json_encode($userId));
        }

        return $user;
    }

    /**
     * Same crediting path as the synchronous FinancialController::webhook route.
     */
    private function handleBalanceRecharge($userId, string $trxId, KashierSignedAmount $paid, array $payloadData): void
    {
        $user = $this->findUserOrFail($userId, $trxId);

        app(BalanceService::class)->processKashierDepositWebhook($user, $paid, $trxId, $payloadData);
    }

    /**
     * Same fulfilment path as the synchronous points/subscription webhook routes.
     */
    private function handleCheckoutPurchase(array $data, string $purpose): void
    {
        $result = app(KashierCheckoutFulfillment::class)->fulfill($data, $purpose);

        if ($result === KashierCheckoutFulfillment::REJECTED) {
            throw new \Exception("Kashier {$purpose} webhook rejected for transaction ".($data['transactionId'] ?? 'unknown'));
        }
    }

    private function handleBookingPurchase($userId, $trxId, $amountPaid, $metaData)
    {
        // Add booking mapping
        $bookingId = $metaData['booking_id'] ?? null;
        if ($bookingId && class_exists('\Modules\Booking\Models\Booking')) {
            $booking = Booking::find($bookingId);
            if ($booking && $booking->payment_status !== 'paid') {
                $booking->payment_status = 'paid';
                $booking->save();
                Log::info("Kashier booking purchase processed successfully for Booking {$bookingId}");
            }
        }
    }

    private function handleInvoicePayment($trxId, KashierSignedAmount $paid, array $metaData): void
    {
        $invoice = isset($metaData['invoice_id']) ? Invoice::find($metaData['invoice_id']) : null;
        if (! $invoice || $invoice->status === 'paid') {
            return;
        }

        $amountDue = $invoice->unpaid_total();
        $invoiceCurrencyId = (int) ($invoice->currency_id ?? $invoice->currency);
        if (! $invoiceCurrencyId || ! $paid->covers($amountDue, $invoiceCurrencyId)) {
            Log::warning('Kashier invoice payment rejected: paid amount is less than amount due.', [
                'invoice_id' => $invoice->id,
                'trx' => $trxId,
                'amount_due' => $amountDue,
                'invoice_currency_id' => $invoiceCurrencyId,
                'paid' => $paid->amount,
                'paid_currency' => $paid->currencyCode,
            ]);

            throw new \Exception("Kashier payment {$trxId} does not cover invoice {$invoice->id}");
        }

        $invoice->mark_as_paid();
        Log::info('Kashier invoice payment processed.', ['invoice_id' => $invoice->id, 'trx' => $trxId]);
    }

    /**
     * $paid is the signed charge; without it (legacy callers) $amountPaid is compared in the link's own currency.
     */
    private function handlePaymentLink($trxId, $amountPaid, $metaData, ?KashierSignedAmount $paid = null)
    {
        $paymentLinkId = $metaData['payment_link_id'] ?? null;
        if (! $paymentLinkId) {
            return;
        }

        DB::transaction(function () use ($paymentLinkId, $trxId, $amountPaid, $paid) {
            $paymentLink = PaymentLink::lockForUpdate()->find($paymentLinkId);
            if (! $paymentLink) {
                Log::warning("Kashier payment link webhook: link {$paymentLinkId} not found.");

                return;
            }

            if ($paymentLink->status === PaymentLink::STATUS_PAID) {
                Log::info("Kashier payment link webhook: link {$paymentLinkId} already paid, idempotent skip.");

                return;
            }

            $amountMatches = $paid
                ? $paid->covers((float) $paymentLink->amount, (int) $paymentLink->currency_id)
                : (float) $paymentLink->amount === (float) $amountPaid;

            if (! $amountMatches) {
                Log::warning("Kashier payment link webhook amount mismatch for link {$paymentLinkId}: expected {$paymentLink->amount}, got {$amountPaid}.");

                throw new \Exception("Payment amount mismatch for payment link {$paymentLinkId}");
            }

            $paymentLink->markPaid(PaymentLink::METHOD_KASHIER, (string) $trxId);
            Log::info("Kashier payment link processed successfully for Link {$paymentLinkId}");
        });
    }
}
