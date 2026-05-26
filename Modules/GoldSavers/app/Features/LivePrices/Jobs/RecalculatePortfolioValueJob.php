<?php

namespace Modules\GoldSavers\app\Features\LivePrices\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\GoldSavers\app\Features\LivePrices\Models\GoldLivePrice;
use Modules\GoldSavers\Models\GoldWallet;

/**
 * Recalculates portfolio value for all of a tenant's wallets
 * whenever a new live price is available.
 */
class RecalculatePortfolioValueJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 60;

    public function __construct(
        public readonly int    $tenantId,
        public readonly string $marketKey,
    ) {
        $this->onQueue('gold-portfolio');
    }

    public function handle(): void
    {
        // Load fresh live prices for this market
        $livePrices = GoldLivePrice::where('tenant_id', $this->tenantId)
            ->get()
            ->keyBy('market_key');

        // Load all wallets with transactions for this tenant
        $wallets = GoldWallet::where('tenant_id', $this->tenantId)
            ->where('is_active', true)
            ->with('transactions')
            ->get();

        foreach ($wallets as $wallet) {
            $this->recalculateWallet($wallet, $livePrices);
        }
    }

    protected function recalculateWallet(GoldWallet $wallet, $livePrices): void
    {
        $livePrice = $livePrices->first(); // Use first available market price

        if (!$livePrice) return;

        // Calculate current market value of all holdings
        $currentValue   = 0;
        $costBasis      = 0;

        foreach ($wallet->transactions as $tx) {
            if (in_array($tx->type, ['buy', 'transfer_in'])) {
                $pricePerGram  = $livePrice->gramPriceForKarat((int) $tx->karat);
                $currentValue += $tx->grams * $pricePerGram;
                $costBasis    += $tx->total_amount;
            } elseif (in_array($tx->type, ['sell', 'transfer_out'])) {
                $pricePerGram  = $livePrice->gramPriceForKarat((int) $tx->karat);
                $currentValue -= $tx->grams * $pricePerGram;
                $costBasis    -= $tx->total_amount;
            }
        }

        $unrealizedPnl = $currentValue - $costBasis;
        $pnlPct        = $costBasis > 0
            ? round(($unrealizedPnl / $costBasis) * 100, 4)
            : 0;

        $wallet->update([
            'current_value'     => round($currentValue, 4),
            'unrealized_pnl'    => round($unrealizedPnl, 4),
            'pnl_pct'           => $pnlPct,
            'last_valuation_at' => now(),
        ]);
    }
}
