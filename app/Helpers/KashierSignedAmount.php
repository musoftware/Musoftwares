<?php

namespace App\Helpers;

use App\Models\CurrenciesExchange;
use App\Models\Currency;
use Illuminate\Support\Facades\Log;

/**
 * The amount Kashier actually charged, read only from signed webhook fields (data.amount + data.currency).
 * metaData.original_amount comes from the checkout URL, which the payer can edit, so it is never trusted.
 */
final class KashierSignedAmount
{
    /** Allowed drift when the expected amount is in another currency (rate can move between checkout and webhook). */
    private const CONVERSION_TOLERANCE_RATIO = 0.01;

    /** Allowed drift when no conversion happens (rounding only). */
    private const SAME_CURRENCY_TOLERANCE = 0.01;

    private function __construct(
        public readonly float $amount,
        public readonly int $currencyId,
        public readonly string $currencyCode,
    ) {}

    /**
     * Build from the webhook "data" object. Returns null (and logs) when the amount is not usable.
     */
    public static function fromWebhookData(array $data): ?self
    {
        $signedKeys = $data['signatureKeys'] ?? null;
        if (is_array($signedKeys) && ! in_array('amount', $signedKeys, true)) {
            Log::warning('Kashier webhook amount is not covered by the signature.', ['signatureKeys' => $signedKeys]);

            return null;
        }

        $amount = (float) ($data['amount'] ?? 0);
        $currencyCode = strtoupper((string) ($data['currency'] ?? 'EGP'));
        $currencyId = Currency::where('currency', $currencyCode)->value('id');

        if ($amount <= 0 || ! $currencyId) {
            Log::warning('Kashier webhook has an unusable signed amount or currency.', [
                'amount' => $data['amount'] ?? null,
                'currency' => $currencyCode,
                'transactionId' => $data['transactionId'] ?? null,
            ]);

            return null;
        }

        return new self($amount, (int) $currencyId, $currencyCode);
    }

    /**
     * The charged amount converted to the target currency (today's rate).
     *
     * @throws \App\Exceptions\MissingExchangeRateException
     */
    public function inCurrency(int $targetCurrencyId): float
    {
        if ($targetCurrencyId === $this->currencyId) {
            return round($this->amount, 2);
        }

        return (float) CurrenciesExchange::RateToday($this->amount, $this->currencyId, $targetCurrencyId);
    }

    /**
     * True when the charged amount pays at least $expected (in $expectedCurrencyId).
     */
    public function covers(float $expected, int $expectedCurrencyId): bool
    {
        $paid = $this->inCurrency($expectedCurrencyId);
        $tolerance = $expectedCurrencyId === $this->currencyId
            ? self::SAME_CURRENCY_TOLERANCE
            : $expected * self::CONVERSION_TOLERANCE_RATIO;

        return $paid + $tolerance >= $expected;
    }
}
