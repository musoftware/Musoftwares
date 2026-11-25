<?php

namespace App\Services;

use App\Exceptions\MissingExchangeRateException;
use App\Helpers\FinanceHelper;
use App\Models\AdminSettings;
use App\Models\CurrenciesExchange;
use App\Models\InvoiceItem;
use App\Models\InvoiceItemTimer;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Prices a timed task as a service, not as minutes. The price is the time a normal freelancer
 * needs (market time) at this client's hourly rate, plus a bonus for starting soon after the
 * client asked and a bonus for this client's earlier ratings. The real sessions are never
 * lowered; when the service price is higher, the gap is kept as one adjustment timer line.
 */
final class ServicePricing
{
    /** The hourly rates for the item's invoice currency. Rates are null when no exchange rate exists. */
    public static function rates(InvoiceItem $item): array
    {
        $invoice = $item->invoice;
        $system = self::convert(FinanceHelper::calculateOverheadHourlyRate(), AdminSettings::GetValue('business_currency', 2), $invoice->currency_id, $item->id);

        $user = $invoice->user;
        $client = 0.0;
        if ($user && (float) ($user->hour_rate ?? 0) > 0) {
            $from = $user->hour_rate_currency_id ?? $user->hour_rate_currency ?? $user->currency_id ?? 1;
            $client = self::convert($user->hour_rate, $from, $invoice->currency_id, $item->id) ?? 0.0;
        }

        $isCustom = (bool) ($user->enable_custom_hour_rate ?? false);
        $effective = ($isCustom && $client > 0) ? $client : $system;

        return ['system' => $system, 'client' => $client, 'is_custom' => $isCustom, 'effective' => $effective];
    }

    /** The full price breakdown for one task. */
    public static function quote(InvoiceItem $item, ?float $hourRate): array
    {
        $sessions = $item->timers()->where('is_service_adjustment', false)->get();
        $sessionsTotal = (float) $sessions->sum('amount');
        $responseMinutes = self::responseMinutes($item, $sessions->min('date_start'));
        $rating = self::clientRating($item);

        $startBonus = self::bonus(config('service_pricing.start_bonus', []), $responseMinutes, fn ($value, $limit) => $value <= $limit);
        $qualityBonus = self::bonus(config('service_pricing.quality_bonus', []), $rating['average'], fn ($value, $limit) => $value >= $limit);

        $marketValue = ((int) $item->market_minutes / 60) * (float) $hourRate;
        $serviceValue = $marketValue * (1 + ($startBonus + $qualityBonus) / 100);
        $minimum = TimerMinimumCharge::inCurrency($item->invoice->currency_id, $item->id) ?? 0.0;
        $hasWork = $sessions->isNotEmpty() || (int) $item->market_minutes > 0;
        $floor = $hasWork ? max($serviceValue, $minimum) : 0.0;

        return [
            'market_minutes' => $item->market_minutes,
            'market_value' => round($marketValue, 2),
            'response_minutes' => $responseMinutes,
            'start_bonus_percent' => $startBonus,
            'client_average_rating' => $rating['average'],
            'client_ratings_count' => $rating['count'],
            'quality_bonus_percent' => $qualityBonus,
            'service_value' => round($serviceValue, 2),
            'minimum' => $minimum > 0 ? round($minimum, 2) : null,
            'floor' => round($floor, 2),
            'sessions_total' => round($sessionsTotal, 2),
            'price' => round(max($floor, $sessionsTotal), 2),
        ];
    }

    /** Keeps the adjustment line equal to the gap between the service price and the real sessions. */
    public static function sync(InvoiceItem $item): void
    {
        $quote = self::quote($item, self::rates($item)['effective']);
        $gap = round($quote['floor'] - $quote['sessions_total'], 3);
        $adjustment = $item->timers()->where('is_service_adjustment', true)->first();

        if ($gap <= 0) {
            $adjustment?->delete();

            return;
        }

        $at = $item->timers()->where('is_service_adjustment', false)->max('date_end') ?? now()->toDateTimeString();
        $adjustment ??= new InvoiceItemTimer(['invoice_item_id' => $item->id, 'is_service_adjustment' => true]);
        $adjustment->fill([
            'date_start' => $at,
            'date_end' => $at,
            'amount' => $gap,
            'project_id' => $item->invoice->project_id,
            'user_id' => $item->invoice->user_id,
            'currency_id' => $item->invoice->currency_id,
        ])->save();
    }

    private static function responseMinutes(InvoiceItem $item, ?string $firstStart): ?int
    {
        if (! $item->requested_at || ! $firstStart) {
            return null;
        }

        return (int) max(0, $item->requested_at->diffInMinutes(Carbon::parse($firstStart), false));
    }

    /** This client's average rating of their other tasks. */
    private static function clientRating(InvoiceItem $item): array
    {
        $rated = InvoiceItem::query()
            ->whereNotNull('client_rating')
            ->where('id', '!=', $item->id)
            ->whereHas('invoice', fn ($q) => $q->where('user_id', $item->invoice->user_id));

        $count = (clone $rated)->count();

        return ['average' => $count > 0 ? round((float) $rated->avg('client_rating'), 2) : null, 'count' => $count];
    }

    /** The bonus from the first table row whose limit matches the value; 0 when the value is unknown. */
    private static function bonus(array $table, int|float|null $value, callable $matches): int
    {
        if ($value === null) {
            return 0;
        }

        foreach ($table as [$limit, $percent]) {
            if ($matches($value, $limit)) {
                return (int) $percent;
            }
        }

        return 0;
    }

    private static function convert(float|string $amount, mixed $from, mixed $to, int $itemId): ?float
    {
        try {
            return (float) CurrenciesExchange::RateToday($amount, $from, $to);
        } catch (MissingExchangeRateException $e) {
            Log::warning('Service pricing rate unavailable: missing exchange rate.', ['invoice_item_id' => $itemId, 'error' => $e->getMessage()]);

            return null;
        }
    }
}
