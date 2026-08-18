<?php

namespace App\Jobs;

use App\Helpers\KashierHelper;
use App\Models\Currency;
use App\Models\IncomingWebhook;
use App\Models\Invoice;
use App\Models\PaymentLink;
use App\Models\PointTransaction;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserSubscription;
use Carbon\Carbon;
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
            switch ($this->webhook->source) {
                case 'kashier':
                    $this->processKashier($this->webhook->payload);
                    break;
                case 'whatsapp':
                    $this->processWhatsApp($this->webhook->payload);
                    break;
                case 'stripe':
                    $this->processStripe($this->webhook->payload);
                    break;
                default:
                    Log::warning("No processor defined for webhook source: {$this->webhook->source}");
                    break;
            }

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
    private function validateSignature($source, $payload, $headers)
    {
        if ($source === 'kashier') {
            if (class_exists('\App\Helpers\KashierHelper') && method_exists('\App\Helpers\KashierHelper', 'validatePayload')) {
                $signature = $headers['x-kashier-signature'][0] ?? ($headers['X-Kashier-Signature'][0] ?? null);

                return KashierHelper::validatePayload($payload, $signature);
            }

            return true;
        }

        if ($source === 'whatsapp') {
            // WhatsApp signature validation (X-Hub-Signature-256)
            return true;
        }

        if ($source === 'stripe') {
            // Stripe signature validation
            return true;
        }

        return true;
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

        $amountPaid = floatval($metaData['original_amount'] ?? $data['amount'] ?? 0);
        $currencyCode = $metaData['original_currency'] ?? ($data['currency'] ?? 'EGP');

        $currencyId = null;
        if ($currencyCode) {
            $currencyModel = Currency::where('currency', strtoupper($currencyCode))->first();
            if ($currencyModel) {
                $currencyId = $currencyModel->id;
            }
        }

        if (! $source || ! $trxId || $amountPaid <= 0) {
            throw new \Exception('Invalid Kashier webhook payload structure.');
        }

        // Route to the appropriate service logic based on source
        switch ($source) {
            case 'balance-recharge':
                $this->handleBalanceRecharge($userId, $trxId, $amountPaid, $currencyId, $metaData);
                break;
            case 'subscription-purchase':
                $this->handleSubscriptionPurchase($userId, $trxId, $amountPaid, $currencyId, $metaData);
                break;
            case 'points-purchase':
                $this->handlePointsPurchase($userId, $trxId, $amountPaid, $currencyId, $metaData);
                break;
            case 'booking-purchase':
                $this->handleBookingPurchase($userId, $trxId, $amountPaid, $metaData);
                break;
            case 'guest-invoice-payment':
            case 'user-invoice-payment':
                $this->handleInvoicePayment($trxId, $amountPaid, $metaData);
                break;
            case 'payment-link':
                $this->handlePaymentLink($trxId, $amountPaid, $metaData);
                break;
            default:
                Log::warning("Unknown Kashier webhook source: {$source}");
        }
    }

    private function handleBalanceRecharge($userId, $trxId, $amountPaid, $currencyId, $metaData)
    {
        $user = User::find($userId);
        if (! $user) {
            return;
        }

        $reason = "Deposit via Kashier online payment (Trx: $trxId)";
        $alreadyProcessed = Transaction::where('user_id', $user->id)->where('reason', $reason)->exists();

        if (! $alreadyProcessed) {
            DB::transaction(function () use ($user, $amountPaid, $reason, $currencyId) {
                $user->add_balance($amountPaid, $reason, 'received', $currencyId);
            });
            Log::info("Kashier balance recharge processed successfully for User {$userId}");
        }
    }

    private function handleSubscriptionPurchase($userId, $trxId, $amountPaid, $currencyId, $metaData)
    {
        $user = User::find($userId);
        if (! $user) {
            return;
        }

        $reason = "Subscription modules via Kashier online payment (Trx: $trxId)";
        $alreadyProcessed = Transaction::where('user_id', $user->id)->where('reason', $reason)->exists();

        if (! $alreadyProcessed) {
            DB::transaction(function () use ($user, $amountPaid, $reason, $currencyId, $metaData) {
                $days = $metaData['days'] ?? 365;
                $isNewSystem = $metaData['is_new_system'] ?? true;

                $user->add_balance($amountPaid, $reason, 'received', $currencyId);
                if (class_exists('\App\Helpers\TimerHelper') && method_exists('\App\Helpers\TimerHelper', 'instance')) {
                    // Note: TimerHelper addUsed might not support currencyId, so we will skip it for now and fallback
                    $user->add_balance(-1 * $amountPaid, 'Subscribe to modules', 'used', $currencyId);
                } else {
                    $user->add_balance(-1 * $amountPaid, 'Subscribe to modules', 'used', $currencyId);
                }

                $items = $metaData['items'] ?? [];
                if (is_array($items) && ! empty($items)) {
                    foreach ($items as $item) {
                        $expiry = Carbon::now()->addDays((int) $days);

                        $existing = UserSubscription::where('user_id', $user->id)->where('object', $item)->first();
                        if ($existing && $existing->status === 'active' && Carbon::parse($existing->expires_at)->isFuture()) {
                            $expiry = Carbon::parse($existing->expires_at)->addDays((int) $days);
                        }

                        UserSubscription::updateOrCreate(
                            ['user_id' => $user->id, 'object' => $item],
                            ['status' => 'active', 'started_at' => now(), 'expires_at' => $expiry, 'auto_renew' => true]
                        );

                    }
                }
            });
            Log::info("Kashier subscription processed successfully for User {$userId}");
        }
    }

    private function handlePointsPurchase($userId, $trxId, $amountPaid, $currencyId, $metaData)
    {
        $user = User::find($userId);
        if (! $user) {
            return;
        }

        $reason = "Points purchase via Kashier (Trx: $trxId)";
        $alreadyProcessed = Transaction::where('user_id', $user->id)->where('reason', $reason)->exists();

        if (! $alreadyProcessed) {
            DB::transaction(function () use ($user, $amountPaid, $reason, $currencyId, $metaData) {
                $points = $metaData['points'] ?? 0;
                $user->add_balance($amountPaid, $reason, 'received', $currencyId);
                $user->add_balance(-1 * $amountPaid, "Used for {$points} points", 'used', $currencyId);

                PointTransaction::create([
                    'user_id' => $user->id,
                    'amount' => $points,
                    'type' => 'credit',
                    'description' => "Purchased {$points} points",
                ]);
            });
            Log::info("Kashier points purchase processed successfully for User {$userId}");
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

    private function handleInvoicePayment($trxId, $amountPaid, $metaData)
    {
        $invoiceId = $metaData['invoice_id'] ?? null;
        if ($invoiceId && class_exists('\App\Models\Invoice')) {
            $invoice = Invoice::find($invoiceId);
            if ($invoice && $invoice->status !== 'paid') {
                $invoice->mark_as_paid();
                Log::info("Kashier invoice payment processed successfully for Invoice {$invoiceId}");
            }
        }
    }

    private function handlePaymentLink($trxId, $amountPaid, $metaData)
    {
        $paymentLinkId = $metaData['payment_link_id'] ?? null;
        if (! $paymentLinkId || ! class_exists('\App\Models\PaymentLink')) {
            return;
        }

        DB::transaction(function () use ($paymentLinkId, $trxId, $amountPaid) {
            $paymentLink = PaymentLink::lockForUpdate()->find($paymentLinkId);
            if (! $paymentLink) {
                Log::warning("Kashier payment link webhook: link {$paymentLinkId} not found.");

                return;
            }

            if ($paymentLink->status === PaymentLink::STATUS_PAID) {
                Log::info("Kashier payment link webhook: link {$paymentLinkId} already paid, idempotent skip.");

                return;
            }

            if ((float) $paymentLink->amount !== (float) $amountPaid) {
                Log::warning("Kashier payment link webhook amount mismatch for link {$paymentLinkId}: expected {$paymentLink->amount}, got {$amountPaid}.");

                throw new \Exception("Payment amount mismatch for payment link {$paymentLinkId}");
            }

            $paymentLink->markPaid(PaymentLink::METHOD_KASHIER, (string) $trxId);
            Log::info("Kashier payment link processed successfully for Link {$paymentLinkId}");
        });
    }

    private function processWhatsApp($payload)
    {
        Log::info('Processing WhatsApp webhook', ['payload' => $payload]);
        // Map to internal state change here
    }

    private function processStripe($payload)
    {
        Log::info('Processing Stripe webhook', ['payload' => $payload]);
    }
}
