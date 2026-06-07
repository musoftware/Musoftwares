<?php

namespace App\Traits;

use App\Models\Currency;
use App\Models\CurrenciesExchange;

/**
 * Global currency conversion trait for Controllers.
 *
 * Provides reusable methods to:
 * - Resolve a user's Currency model from their currency_id
 * - Convert a single amount between currencies (with optional historical date)
 * - Sum a collection of items, converting each to a target currency using per-item dates
 * - Prepare currency data for the frontend
 *
 * Usage:
 *   use \App\Traits\ConvertsCurrency;
 *
 *   class MyController extends Controller {
 *       use ConvertsCurrency;
 *       ...
 *   }
 */
trait ConvertsCurrency
{
    /**
     * In-memory cache of Currency models to avoid repeated DB lookups within a request.
     */
    protected array $_currencyModelCache = [];

    // ── Currency Model Resolution ────────────────────────────────

    /**
     * Get a Currency model by its ID (cached per request).
     */
    protected function getCurrencyModel(int $id): ?Currency
    {
        if (!isset($this->_currencyModelCache[$id])) {
            $this->_currencyModelCache[$id] = Currency::find($id);
        }
        return $this->_currencyModelCache[$id];
    }

    /**
     * Get the Currency model for a given user (from their currency_id column).
     * If no user is provided, uses the authenticated user.
     * Fails loudly if the user currency is not configured or not found.
     *
     * @param  \App\Models\User|null  $user
     * @return Currency
     * @throws \Exception
     */
    protected function getUserCurrencyObject($user = null): Currency
    {
        $user = $user ?? auth()->user();
        if (!$user || !$user->currency_id) {
            throw new \Exception(__('errors.currency_configuration_missing') ?: 'User currency is not configured.');
        }
        
        $currency = $this->getCurrencyModel($user->currency_id);
        if (!$currency) {
            throw new \Exception("Currency ID {$user->currency_id} not found in database.");
        }
        
        return $currency;
    }

    // ── Single Amount Conversion ─────────────────────────────────

    /**
     * Convert a single amount from one currency to another.
     *
     * If $date is provided, uses historical rate (RateByDate).
     * If $date is null, uses the latest available rate (RateToday).
     *
     * @param  float       $amount          The amount to convert
     * @param  int         $fromCurrencyId  Source currency ID
     * @param  int         $toCurrencyId    Target currency ID
     * @param  string|null $date            Optional date for historical rate (Y-m-d or Carbon-parseable)
     * @return float
     */
    protected function convertToUserCurrency(
        float $amount,
        int $fromCurrencyId,
        int $toCurrencyId,
        ?string $date = null
    ): float {
        if ($fromCurrencyId === $toCurrencyId || $amount == 0) {
            return round($amount, 2);
        }

        if ($date) {
            return CurrenciesExchange::RateByDate($date, $amount, $fromCurrencyId, $toCurrencyId);
        }

        return CurrenciesExchange::RateToday($amount, $fromCurrencyId, $toCurrencyId);
    }

    // ── Collection Summation ─────────────────────────────────────

    /**
     * Sum a collection of items, converting each to a target currency.
     *
     * Each item's amount is converted using its own date (for historical accuracy)
     * or using today's rate if no date field is specified.
     *
     * Example:
     *   $total = $this->sumInUserCurrency(
     *       $contracts,       // Collection or array of items
     *       'amount',         // The field containing the monetary amount
     *       'currency_id',    // The field containing the source currency ID
     *       $user->currency_id, // Target currency to convert into
     *       'started_at'      // Optional: date field for historical rate per item
     *   );
     *
     * @param  iterable    $items           Collection or array of items
     * @param  string      $amountField     Field name for the amount on each item
     * @param  string      $currencyField   Field name for the currency_id on each item
     * @param  int         $toCurrencyId    Target currency ID
     * @param  string|null $dateField       Optional: field name for the date (for RateByDate per item)
     * @return float
     */
    protected function sumInUserCurrency(
        $items,
        string $amountField,
        string $currencyField,
        int $toCurrencyId,
        ?string $dateField = null
    ): float {
        $total = 0.0;

        foreach ($items as $item) {
            $amount = (float) (is_array($item) ? ($item[$amountField] ?? 0) : ($item->$amountField ?? 0));
            $fromCurrency = (int) (is_array($item) ? ($item[$currencyField] ?? $toCurrencyId) : ($item->$currencyField ?? $toCurrencyId));
            $date = null;

            if ($dateField) {
                $rawDate = is_array($item) ? ($item[$dateField] ?? null) : ($item->$dateField ?? null);
                $date = $rawDate ? (string) $rawDate : null;
            }

            $total += $this->convertToUserCurrency($amount, $fromCurrency, $toCurrencyId, $date);
        }

        return round($total, 2);
    }

    // ── Frontend Helpers ─────────────────────────────────────────

    /**
     * Prepare a currency array to pass to the frontend (Inertia props).
     * Fails loudly if the currency is not found.
     *
     * @param  int  $currencyId
     * @return array  e.g. ['id' => 2, 'currency' => 'EGP', 'symbol' => '£', 'string_format' => '...']
     * @throws \Exception
     */
    protected function currencyForFrontend(int $currencyId): array
    {
        $model = $this->getCurrencyModel($currencyId);
        if (!$model) {
            throw new \Exception("Currency ID {$currencyId} not found in database.");
        }

        return [
            'id'            => $model->id,
            'currency'      => $model->currency,
            'symbol'        => $model->symbol,
            'string_format' => $model->string_format ?? null,
        ];
    }

    /**
     * Format a given amount using a currency ID or code.
     *
     * @param  float  $amount
     * @param  int|string    $currencyId
     * @return string
     */
    protected function formatAmount(float $amount, $currencyId): string
    {
        return \App\Helpers\FinanceHelper::instance()->format_money($amount, $currencyId);
    }

    // ── Inline Model Conversion Helpers ──────────────────────────

    /**
     * Convert a model's amount + currency_id to the user's currency in-place.
     * Also sets the currency relation on the model and appends a formatted string attribute.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $amountField     e.g. 'amount', 'bid_amount', 'budget'
     * @param  string  $currencyField   e.g. 'currency_id'
     * @param  int     $toCurrencyId    Target currency ID
     * @param  string|null  $date       Optional date for historical rate
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function convertModelCurrency(
        $model,
        string $amountField,
        string $currencyField,
        int $toCurrencyId,
        ?string $date = null
    ) {
        $fromCurrency = (int) ($model->$currencyField ?? $toCurrencyId);

        if ($fromCurrency !== $toCurrencyId && $fromCurrency) {
            $model->$amountField = $this->convertToUserCurrency(
                (float) ($model->$amountField ?? 0),
                $fromCurrency,
                $toCurrencyId,
                $date
            );
        }

        $model->$currencyField = $toCurrencyId;
        $currencyModel = $this->getCurrencyModel($toCurrencyId);
        if ($currencyModel) {
            $model->setRelation('currency', $currencyModel);
        }

        return $model;
    }
}
