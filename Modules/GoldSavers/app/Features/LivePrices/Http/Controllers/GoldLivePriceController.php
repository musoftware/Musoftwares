<?php

namespace Modules\GoldSavers\app\Features\LivePrices\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\GoldSavers\app\Features\LivePrices\Http\Resources\GoldLivePriceResource;
use Modules\GoldSavers\app\Features\LivePrices\Jobs\FetchLiveGoldPriceJob;
use Modules\GoldSavers\app\Features\LivePrices\Models\GoldLivePrice;
use Modules\GoldSavers\app\Features\LivePrices\Models\GoldMarketSource;
use Modules\GoldSavers\app\Features\LivePrices\Services\GoldLivePriceLimitsService;
use Modules\GoldSavers\app\Features\LivePrices\Services\GoldLivePriceService;
use Modules\GoldSavers\app\Features\LivePrices\Services\GoldMarketProviderManager;

class GoldLivePriceController extends Controller
{
    public function __construct(
        protected GoldLivePriceLimitsService  $limitsService,
        protected GoldLivePriceService        $priceService,
        protected GoldMarketProviderManager   $providerManager,
    ) {}

    /**
     * GET /api/gold/live-prices
     * Returns all live prices for the current tenant.
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        if (!$this->limitsService->canUse($tenantId)) {
            return response()->json(['upsell' => true, 'message' => 'Upgrade to access live gold prices.'], 402);
        }

        $query = GoldLivePrice::where('tenant_id', $tenantId)
            ->with('source')
            ->fresh();

        if ($request->filled('currency')) {
            $query->where('currency', $request->currency);
        }

        if ($request->filled('market_key')) {
            $query->where('market_key', $request->market_key);
        }

        $prices = $query->get();

        return GoldLivePriceResource::collection($prices)->response();
    }

    /**
     * GET /api/gold/live-prices/{market_key}
     * Returns the live price for a specific market.
     */
    public function show(string $marketKey): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        if (!$this->limitsService->canUse($tenantId)) {
            return response()->json(['upsell' => true], 402);
        }

        $livePrice = $this->priceService->getCachedLivePrice($tenantId, $marketKey);

        if (!$livePrice) {
            return response()->json(['message' => 'Market not found'], 404);
        }

        return (new GoldLivePriceResource($livePrice))->response();
    }

    /**
     * GET /api/gold/live-prices/karats
     * Returns karat breakdown for all markets.
     */
    public function karats(): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        if (!$this->limitsService->canUse($tenantId)) {
            return response()->json(['upsell' => true], 402);
        }

        $prices = GoldLivePrice::where('tenant_id', $tenantId)->fresh()->get();

        $karats = $prices->map(fn ($p) => [
            'market_key' => $p->market_key,
            'currency'   => $p->currency,
            'karats'     => [
                '24K' => ['price' => $p->price_gram_24k, 'buy' => $p->buy_price, 'sell' => $p->sell_price],
                '21K' => ['price' => $p->price_gram_21k],
                '18K' => ['price' => $p->price_gram_18k],
                '14K' => ['price' => $p->price_gram_14k],
            ],
            'spread'     => $p->spread,
            'direction'  => $p->direction,
            'delta_pct'  => $p->price_delta_pct,
            'fetched_at' => $p->fetched_at?->toISOString(),
        ]);

        return response()->json(['data' => $karats]);
    }

    /**
     * POST /api/gold/live-prices/{market_key}/refresh
     * Manually triggers a price refresh for a market.
     */
    public function refresh(string $marketKey): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        if (!$this->limitsService->canUse($tenantId)) {
            return response()->json(['upsell' => true], 402);
        }

        if (!$this->limitsService->canRefresh()) {
            return response()->json([
                'message'   => 'Manual refresh limit reached.',
                'remaining' => $this->limitsService->getRemainingUsage('max_price_refreshes'),
            ], 429);
        }

        $this->limitsService->increaseUsage('max_price_refreshes');

        FetchLiveGoldPriceJob::dispatch($tenantId, $marketKey);

        return response()->json(['message' => 'Refresh queued successfully.']);
    }

    /**
     * GET /api/gold/live-prices/markets
     * Returns all available markets for the tenant.
     */
    public function markets(): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        if (!$this->limitsService->canUse($tenantId)) {
            return response()->json(['upsell' => true], 402);
        }

        $markets = $this->providerManager->availableMarkets($tenantId);

        return response()->json(['data' => $markets]);
    }

    /**
     * Inertia page: Live Prices dashboard.
     */
    public function page()
    {
        $tenantId = auth()->user()->tenant_id;

        if (!$this->limitsService->canUse($tenantId)) {
            return Inertia::render('GoldSavers/LivePrices/Upsell');
        }

        $prices = GoldLivePrice::where('tenant_id', $tenantId)->with('source')->get();

        return Inertia::render('GoldSavers/LivePrices/Index', [
            'livePrices' => GoldLivePriceResource::collection($prices),
            'limits'     => [
                'canRefresh'          => $this->limitsService->canRefresh(),
                'remainingRefreshes'  => $this->limitsService->getRemainingUsage('max_price_refreshes'),
                'canSubscribeRealtime' => $this->limitsService->canSubscribeRealtime(),
            ],
        ]);
    }
}
