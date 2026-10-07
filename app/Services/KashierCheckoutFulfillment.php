<?php

namespace App\Services;

use App\Helpers\KashierHelper;
use App\Helpers\KashierSignedAmount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\KashierCheckout;
use App\Models\PaymentProviderEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Fulfils a paid Kashier checkout (points or subscription modules). Single path for the
 * synchronous controller webhooks and the queued ProcessWebhookJob.
 *
 * Trust model: what is bought comes from the server-side KashierCheckout row; how much was
 * paid comes from the signed data.amount/data.currency; dedup comes from PaymentProviderEvent.
 * metaData only points at the checkout row, and the signed amount must cover that row's price.
 */
class KashierCheckoutFulfillment
{
    public const PROCESSED = 'processed';

    public const DUPLICATE = 'duplicate';

    public const REJECTED = 'rejected';

    public function __construct(
        private readonly PointPurchaseService $points,
        private readonly SubscriptionService $subscriptions,
    ) {}

    /**
     * Shared HTTP handler for the synchronous Kashier checkout webhooks (points, subscriptions).
     */
    public function respondToWebhook(Request $request, string $purpose): JsonResponse
    {
        if (! KashierHelper::validatePayload()) {
            Log::warning('Kashier checkout webhook rejected: invalid signature.', ['purpose' => $purpose]);

            return response()->json(['error' => 'Invalid webhook signature'], 400);
        }

        $data = (array) $request->input('data', []);
        if (($data['status'] ?? null) !== 'SUCCESS') {
            return response()->json(['status' => 'ignored']);
        }

        try {
            $result = $this->fulfill($data, $purpose);
        } catch (\Throwable $e) {
            Log::error('Kashier checkout webhook failed.', ['purpose' => $purpose, 'trx' => $data['transactionId'] ?? null, 'error' => $e->getMessage()]);

            return response()->json(['status' => 'error', 'message' => 'Failed to process payment'], 500);
        }

        return match ($result) {
            self::PROCESSED => response()->json(['status' => 'success', 'message' => 'Payment processed successfully']),
            self::DUPLICATE => response()->json(['status' => 'success', 'message' => 'Already processed']),
            default => response()->json(['status' => 'error', 'message' => 'Payment rejected'], 422),
        };
    }

    /**
     * @param  array  $data  The webhook "data" object (signature already verified by the caller).
     * @return string One of the class constants.
     */
    public function fulfill(array $data, string $purpose): string
    {
        $trxId = (string) ($data['transactionId'] ?? '');
        $metaData = $this->metaData($data);
        $paid = KashierSignedAmount::fromWebhookData($data);
        $checkout = $this->findCheckout($metaData, $purpose);

        if (($data['status'] ?? null) !== 'SUCCESS' || $trxId === '' || ! $paid || ! $checkout) {
            Log::warning('Kashier checkout webhook rejected: missing transaction, amount or checkout.', [
                'trx' => $trxId, 'purpose' => $purpose, 'checkout_id' => $metaData['checkout_id'] ?? null,
            ]);

            return self::REJECTED;
        }

        if (! $paid->covers($checkout->amount, (int) $checkout->currency_id)) {
            Log::warning('Kashier checkout webhook rejected: paid amount is less than the checkout price.', [
                'trx' => $trxId, 'checkout_id' => $checkout->id, 'price' => $checkout->amount,
                'paid' => $paid->amount, 'paid_currency' => $paid->currencyCode,
            ]);

            return self::REJECTED;
        }

        return DB::transaction(fn () => $this->applyOnce($checkout->id, $trxId, $paid, $data));
    }

    private function applyOnce(int $checkoutId, string $trxId, KashierSignedAmount $paid, array $data): string
    {
        if (! PaymentProviderEvent::recordOnce(PaymentProviderEvent::PROVIDER_KASHIER, $trxId, $data)) {
            Log::info('Duplicate Kashier checkout webhook skipped.', ['trx' => $trxId]);

            return self::DUPLICATE;
        }

        $checkout = KashierCheckout::lockForUpdate()->findOrFail($checkoutId);
        if ($checkout->status !== KashierCheckout::STATUS_PENDING) {
            Log::warning('Kashier checkout already fulfilled by another transaction.', ['trx' => $trxId, 'checkout_id' => $checkoutId]);

            return self::DUPLICATE;
        }

        $user = User::lockForUpdate()->findOrFail($checkout->user_id);
        $amountInWallet = $paid->inCurrency((int) $user->currency_id);

        $checkout->purpose === KashierCheckout::PURPOSE_POINTS
            ? $this->fulfillPoints($user, $checkout, $amountInWallet, $trxId)
            : $this->fulfillSubscription($user, $checkout, $amountInWallet, $trxId);

        $checkout->markCompleted($trxId);
        Log::info('Kashier checkout fulfilled.', ['trx' => $trxId, 'checkout_id' => $checkoutId, 'purpose' => $checkout->purpose]);

        return self::PROCESSED;
    }

    private function fulfillPoints(User $user, KashierCheckout $checkout, float $amount, string $trxId): void
    {
        $payload = $checkout->payload;
        $reason = "Points purchase via Kashier online payment (Trx: $trxId)";

        $this->points->processWebhookPurchase($user, $amount, $reason, (int) $payload['points'], $payload['package_id'] ?? null);
    }

    private function fulfillSubscription(User $user, KashierCheckout $checkout, float $amount, string $trxId): void
    {
        $payload = $checkout->payload;
        $reason = "Subscription modules via Kashier online payment (Trx: $trxId)";

        $this->subscriptions->processSubscription(
            $user,
            $amount,
            (int) $payload['days'],
            (array) $payload['items'],
            (bool) ($payload['is_new_system'] ?? false),
            $reason,
            'webhook_received'
        );
    }

    private function findCheckout(array $metaData, string $purpose): ?KashierCheckout
    {
        $checkoutId = $metaData['checkout_id'] ?? null;
        if (! $checkoutId) {
            return null;
        }

        return KashierCheckout::whereKey($checkoutId)
            ->where('purpose', $purpose)
            ->when(isset($metaData['user_id']), fn ($q) => $q->where('user_id', $metaData['user_id']))
            ->first();
    }

    private function metaData(array $data): array
    {
        $metaData = $data['metaData'] ?? [];

        return is_string($metaData) ? (json_decode($metaData, true) ?: []) : (array) $metaData;
    }
}
