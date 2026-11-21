<?php

namespace App\Services;

use App\Exceptions\MissingExchangeRateException;
use App\Models\AdminSettings;
use App\Models\CurrenciesExchange;
use App\Models\InvoiceItem;
use App\Models\InvoiceItemTimer;
use Illuminate\Support\Facades\Log;

/**
 * The smallest price one timed task can cost (like a marketplace minimum order, for example
 * Khamsat's 5 USD). The admin sets the amount and its currency in settings; 0 turns it off.
 */
final class TimerMinimumCharge
{
    public const AMOUNT_KEY = 'timer_minimum_charge';

    public const CURRENCY_KEY = 'timer_minimum_charge_currency';

    /** The minimum in the given currency, or null when it is off or no exchange rate exists. */
    public static function inCurrency(mixed $currencyId, int $itemId): ?float
    {
        $amount = (float) AdminSettings::GetValue(self::AMOUNT_KEY, 0);
        if ($amount <= 0) {
            return null;
        }

        $from = AdminSettings::GetValue(self::CURRENCY_KEY) ?: AdminSettings::GetValue('business_currency', 2);

        try {
            return (float) CurrenciesExchange::RateToday($amount, $from, $currencyId);
        } catch (MissingExchangeRateException $e) {
            Log::warning('Timer minimum charge skipped: missing exchange rate.', ['invoice_item_id' => $itemId, 'error' => $e->getMessage()]);

            return null;
        }
    }

    /** Raises the newest session so all the item's sessions together reach the minimum. */
    public static function apply(InvoiceItem $item, InvoiceItemTimer $newest): void
    {
        $minimum = self::inCurrency($newest->currency_id, $item->id);
        if ($minimum === null) {
            return;
        }

        $gap = round($minimum - (float) $item->timers()->sum('amount'), 3);
        if ($gap <= 0) {
            return;
        }

        $newest->amount = (float) $newest->amount + $gap;
        $newest->save();
    }
}
