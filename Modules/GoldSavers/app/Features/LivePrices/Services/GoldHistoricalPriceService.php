<?php

namespace Modules\GoldSavers\app\Features\LivePrices\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Modules\GoldSavers\app\Features\LivePrices\Models\GoldPriceHistory;
use Modules\GoldSavers\app\Features\LivePrices\Models\GoldPriceSnapshot;

/**
 * Queries and formats historical price data for charts.
 * Supports: minute, hour, day, week, month intervals.
 * Returns OHLCV-ready data structures for charting libraries.
 */
class GoldHistoricalPriceService
{
    protected array $validIntervals = ['minute', 'hour', 'day', 'week', 'month'];

    /**
     * Get OHLCV candle data for charting.
     */
    public function getCandles(
        int    $tenantId,
        string $marketKey,
        string $interval,
        Carbon $from,
        Carbon $to,
        int    $karat = 24,
        string $currency = 'EGP',
    ): Collection {
        abort_unless(in_array($interval, $this->validIntervals), 400, 'Invalid interval');

        $cacheKey = "gold.history.{$tenantId}.{$marketKey}.{$interval}.{$karat}.{$from->timestamp}.{$to->timestamp}";

        return Cache::remember($cacheKey, 300, function () use ($tenantId, $marketKey, $interval, $karat, $from, $to) {
            return GoldPriceHistory::forTenant($tenantId)
                ->forMarket($marketKey)
                ->forInterval($interval)
                ->forKarat($karat)
                ->inPeriod($from, $to)
                ->orderBy('period_start')
                ->get(['period_start', 'open_price', 'high_price', 'low_price', 'close_price', 'avg_price', 'tick_count']);
        });
    }

    /**
     * Aggregate snapshots for a given period into OHLCV and store in history.
     * Called by AggregateHistoricalPricesJob.
     */
    public function aggregate(
        int    $tenantId,
        string $marketKey,
        string $interval,
        Carbon $periodStart,
        Carbon $periodEnd,
        int    $karat = 24,
    ): void {
        $column = match ($karat) {
            24      => 'price_gram_24k',
            21      => 'price_gram_21k',
            18      => 'price_gram_18k',
            14      => 'price_gram_14k',
            default => 'price_gram_24k',
        };

        $snapshots = GoldPriceSnapshot::forTenant($tenantId)
            ->forMarket($marketKey)
            ->valid()
            ->whereBetween('fetched_at', [$periodStart, $periodEnd])
            ->orderBy('fetched_at')
            ->get([$column, 'fetched_at', 'currency']);

        if ($snapshots->isEmpty()) return;

        $prices = $snapshots->pluck($column)->map(fn ($p) => (float) $p);

        GoldPriceHistory::updateOrCreate(
            [
                'tenant_id'    => $tenantId,
                'market_key'   => $marketKey,
                'interval'     => $interval,
                'karat'        => $karat,
                'period_start' => $periodStart,
            ],
            [
                'source_id'   => null,
                'period_end'  => $periodEnd,
                'open_price'  => $prices->first(),
                'high_price'  => $prices->max(),
                'low_price'   => $prices->min(),
                'close_price' => $prices->last(),
                'avg_price'   => round($prices->avg(), 4),
                'tick_count'  => $prices->count(),
                'currency'    => $snapshots->first()?->currency ?? 'EGP',
            ]
        );
    }

    /**
     * Get price volatility metrics for a market.
     */
    public function getVolatility(int $tenantId, string $marketKey, int $days = 30): array
    {
        $candles = GoldPriceHistory::forTenant($tenantId)
            ->forMarket($marketKey)
            ->forInterval('day')
            ->where('period_start', '>=', now()->subDays($days))
            ->orderBy('period_start')
            ->get(['close_price', 'high_price', 'low_price']);

        if ($candles->isEmpty()) return ['volatility' => 0, 'avg_range' => 0];

        $avgRange = $candles->avg(fn ($c) => $c->high_price - $c->low_price);

        return [
            'volatility' => round($avgRange, 4),
            'avg_range'  => round($avgRange, 4),
            'days'       => $days,
        ];
    }

    /**
     * Compute the period bounds for a given interval and reference time.
     */
    public function getPeriodBounds(string $interval, Carbon $referenceTime): array
    {
        return match ($interval) {
            'minute' => [
                $referenceTime->copy()->startOfMinute(),
                $referenceTime->copy()->endOfMinute(),
            ],
            'hour' => [
                $referenceTime->copy()->startOfHour(),
                $referenceTime->copy()->endOfHour(),
            ],
            'day' => [
                $referenceTime->copy()->startOfDay(),
                $referenceTime->copy()->endOfDay(),
            ],
            'week' => [
                $referenceTime->copy()->startOfWeek(),
                $referenceTime->copy()->endOfWeek(),
            ],
            'month' => [
                $referenceTime->copy()->startOfMonth(),
                $referenceTime->copy()->endOfMonth(),
            ],
            default => throw new \InvalidArgumentException("Invalid interval: {$interval}"),
        };
    }
}
