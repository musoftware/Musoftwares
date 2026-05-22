<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Core\Models\PointPackage;
use Modules\Core\Models\PointTransaction;
use App\Services\FinanceService;

class PointsDashboardController extends Controller
{
    /**
     * Volume discount tiers (in EGP per point).
     * The more points you buy, the cheaper each point becomes.
     *
     * Format: [min_points, max_points, price_per_point_egp]
     */
    public const PRICING_TIERS = [
        ['min' => 1,     'max' => 99,    'price_per_point' => 1.00],   // 1 EGP/pt
        ['min' => 100,   'max' => 499,   'price_per_point' => 0.90],   // 10% off
        ['min' => 500,   'max' => 999,   'price_per_point' => 0.75],   // 25% off
        ['min' => 1000,  'max' => 4999,  'price_per_point' => 0.60],   // 40% off
        ['min' => 5000,  'max' => 9999,  'price_per_point' => 0.50],   // 50% off
        ['min' => 10000, 'max' => null,  'price_per_point' => 0.40],   // 60% off
    ];

    /**
     * Get the price-per-point (in EGP) for a given quantity.
     */
    public static function getPricePerPoint(int $points): float
    {
        foreach (self::PRICING_TIERS as $tier) {
            if ($points >= $tier['min'] && ($tier['max'] === null || $points <= $tier['max'])) {
                return $tier['price_per_point'];
            }
        }
        return 1.00; // fallback
    }

    /**
     * Calculate total cost in EGP for a given number of points.
     */
    public static function calculateCostEgp(int $points): float
    {
        return round($points * self::getPricePerPoint($points), 2);
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $preferredCurrency = $user->preferred_currency ?: 'USD';

        $financeService = app(FinanceService::class);
        $egpToPreferredRate = $financeService->getExchangeRate('EGP', $preferredCurrency);

        // Convert tiers to user's preferred currency for frontend display
        $tiers = collect(self::PRICING_TIERS)->map(function ($tier) use ($financeService, $preferredCurrency) {
            return [
                'min' => $tier['min'],
                'max' => $tier['max'],
                'price_per_point' => $financeService->convertAmount($tier['price_per_point'], 'EGP', $preferredCurrency),
                'base_price_per_point' => $tier['price_per_point'], // EGP base
                'discount_percent' => round((1 - $tier['price_per_point'] / 1.00) * 100),
            ];
        });

        // Quick-buy packages (pre-calculated convenience packages)
        $quickPackages = collect([
            ['points' => 100,  'label' => 'Starter'],
            ['points' => 500,  'label' => 'Pro'],
            ['points' => 1000, 'label' => 'Business'],
            ['points' => 5000, 'label' => 'Enterprise'],
        ])->map(function ($pkg) use ($financeService, $preferredCurrency) {
            $costEgp = self::calculateCostEgp($pkg['points']);
            $costConverted = $financeService->convertAmount($costEgp, 'EGP', $preferredCurrency);
            $pricePerPoint = $financeService->convertAmount(self::getPricePerPoint($pkg['points']), 'EGP', $preferredCurrency);
            $fullPriceConverted = $financeService->convertAmount($pkg['points'] * 1.00, 'EGP', $preferredCurrency);
            
            return [
                'points' => $pkg['points'],
                'label' => $pkg['label'],
                'total_cost' => $costConverted,
                'price_per_point' => $pricePerPoint,
                'full_price' => $fullPriceConverted,
                'discount_percent' => round((1 - self::getPricePerPoint($pkg['points']) / 1.00) * 100),
                'savings' => $fullPriceConverted - $costConverted,
            ];
        });

        $transactions = PointTransaction::where('user_id', $user->id)->latest()->paginate(10);

        return Inertia::render('Core/Points/Index', [
            'tiers' => $tiers,
            'quickPackages' => $quickPackages,
            'transactions' => $transactions,
            'egpToPreferredRate' => $egpToPreferredRate,
            'currency' => $preferredCurrency,
        ]);
    }
}
