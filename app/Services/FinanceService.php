<?php

namespace App\Services;

use Modules\Core\Models\Currency;
use Modules\Core\Services\ExchangeRateService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

/**
 * Financial service providing consistent pricing, formatting, and calculation utilities.
 * Recovered from old project: App\Helper\FinanceHelper
 * Modernized: Singleton-less, dependency-injected, service-oriented.
 */
class FinanceService
{
    protected ExchangeRateService $exchangeRateService;

    public function __construct(ExchangeRateService $exchangeRateService)
    {
        $this->exchangeRateService = $exchangeRateService;
    }

    // ── Money Formatting ─────────────────────────────────────────

    /**
     * Format a monetary amount with its currency code.
     * Recovered from old project: FinanceHelper::format_money()
     */
    public function formatMoney(float $amount, string $currencyCode = 'USD'): string
    {
        return number_format($amount, 2, '.', ',') . ' ' . strtoupper($currencyCode);
    }

    /**
     * Format money for the current authenticated user's preferred currency.
     * Recovered from old project: FinanceHelper::FormatMoneyCurrentUser()
     */
    public function formatMoneyCurrentUser(float $amount): string
    {
        $user = Auth::user();
        $currency = $user?->preferred_currency ?? 'USD';
        return $this->formatMoney($amount, $currency);
    }

    /**
     * Format money in business (base) currency.
     * Recovered from old project: FinanceHelper::FormatMoneyCurrentBusiness()
     */
    public function formatMoneyBusiness(float $amount): string
    {
        $businessCurrency = config('app.business_currency', 'USD');
        return $this->formatMoney($amount, $businessCurrency);
    }

    // ── Price Fixer ──────────────────────────────────────────────

    /**
     * Round price to a clean display/payment value depending on currency.
     * Used for plan pricing, invoice timer hour rate, and booking cost.
     *
     * Recovered from old project: FinanceHelper::price_fixer()
     *
     * Rules:
     * - EGP: round up to nearest 5 (e.g. 157.99 → 160)
     * - Other (USD etc.): round up by magnitude — 3 digits → step 5, 4 digits → 50, 5+ → 100
     *
     * @param float $price Raw calculated price
     * @param string $currency Currency code (e.g. 'EGP', 'USD')
     * @return float Clean display-ready price
     */
    public function priceFixer(float $price, string $currency = 'USD'): float
    {
        $code = strtoupper($currency);
        if ($price <= 0) {
            return 0.0;
        }

        if ($code === 'EGP') {
            return (float) ((int) ceil($price / 5) * 5);
        }

        $step = 5;
        $magnitude = (int) log10(max(1, (int) round($price)));
        if ($magnitude >= 4) {
            $step = 100;
        } elseif ($magnitude >= 3) {
            $step = 50;
        }

        return (float) ((int) ceil($price / $step) * $step);
    }

    // ── Time Formatting ──────────────────────────────────────────

    /**
     * Convert seconds to a human-readable time string.
     * Recovered from old project: FinanceHelper::secondsToTime()
     */
    public function secondsToTime(int $seconds): string
    {
        $days = (int) floor($seconds / 86400);
        $hours = (int) floor(($seconds - ($days * 86400)) / 3600);
        $minutes = (int) floor(($seconds / 60) % 60);
        $secs = $seconds % 60;

        $parts = [];
        if ($days > 0) $parts[] = "{$days}d";
        $parts[] = "{$hours}h";
        $parts[] = "{$minutes}m";
        $parts[] = "{$secs}s";

        return implode(' ', $parts);
    }

    /**
     * Format seconds as hours with 2 decimal places (for billing).
     */
    public function secondsToHours(int $seconds): float
    {
        return round($seconds / 3600, 2);
    }

    // ── Revenue Calculations ─────────────────────────────────────

    /**
     * Calculate month-over-month growth percentage.
     * Recovered from old project: FinanceHelper::DrawChange() logic
     */
    public function calculateGrowthPercent(float $thisMonth, float $lastMonth): ?float
    {
        if ($lastMonth == 0 && $thisMonth == 0) {
            return null;
        }

        if ($lastMonth == 0) {
            return 100.0;
        }

        return round((($thisMonth - $lastMonth) / $lastMonth) * 100, 1);
    }

    /**
     * Calculate profit margin percentage.
     */
    public function profitMargin(float $revenue, float $costs): float
    {
        if ($revenue <= 0) {
            return 0.0;
        }

        return round((($revenue - $costs) / $revenue) * 100, 1);
    }

    // ── Currency Conversion ──────────────────────────────────────

    /**
     * Convert amount between currencies using the ExchangeRateService.
     * Returns the converted amount (float only).
     */
    public function convertAmount(float $amount, string $from, string $to, $date = null): float
    {
        if ($from === $to) {
            return $amount;
        }

        $rate = $this->exchangeRateService->getRate($from, $to, $date);
        return round($amount * (float) $rate, 2);
    }

    /**
     * Get the current exchange rate between two currencies.
     */
    public function getExchangeRate(string $from, string $to, $date = null): float
    {
        return (float) $this->exchangeRateService->getRate($from, $to, $date);
    }

    // ── Invoice Calculations ─────────────────────────────────────

    /**
     * Calculate tax amount given a subtotal and tax rate.
     */
    public function calculateTax(float $subtotal, float $taxRate): float
    {
        return round($subtotal * ($taxRate / 100), 2);
    }

    /**
     * Calculate invoice total from subtotal, discount, and tax rate.
     */
    public function calculateInvoiceTotal(float $subtotal, float $discount = 0, float $taxRate = 0): array
    {
        $afterDiscount = max(0, $subtotal - $discount);
        $taxAmount = $this->calculateTax($afterDiscount, $taxRate);
        $total = round($afterDiscount + $taxAmount, 2);

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'after_discount' => $afterDiscount,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'total' => $total,
        ];
    }
}
