<?php

namespace App\Services;

use App\Models\CurrenciesExchange;
use App\Models\Currency;
use App\Models\PointPackage;
use App\Models\PointTransaction;
use App\Models\User;

class PointPurchaseService extends BaseService
{
    // Defined tiers for volume pricing when purchasing custom points
    protected array $tiers = [
        ['min' => 1, 'max' => 999, 'price_per_point' => 1.00, 'discount_percent' => 0],
        ['min' => 1000, 'max' => 4999, 'price_per_point' => 0.90, 'discount_percent' => 10],
        ['min' => 5000, 'max' => null, 'price_per_point' => 0.80, 'discount_percent' => 20],
    ];

    /**
     * Get pricing tiers for frontend calculation.
     */
    public function getTiers(): array
    {
        return $this->tiers;
    }

    /**
     * Get predefined point packages enriched with frontend stats.
     */
    public function getQuickPackages(): array
    {
        return PointPackage::all()->map(function ($pkg) {
            $fullPrice = $pkg->points * 1.00; // Base price assumption

            return [
                'id' => $pkg->id,
                'label' => $pkg->name,
                'points' => $pkg->points,
                'full_price' => $fullPrice,
                'total_cost' => $pkg->price,
                'price_per_point' => $pkg->points > 0 ? round($pkg->price / $pkg->points, 2) : 0,
                'savings' => max(0, $fullPrice - $pkg->price),
                'discount_percent' => $fullPrice > 0 ? round((($fullPrice - $pkg->price) / $fullPrice) * 100) : 0,
            ];
        })->toArray();
    }

    /**
     * Fetch point transactions for a user. The page number has its own name, so it can share a page
     * with the loyalty points history.
     */
    public function getUserTransactions(int $userId)
    {
        return PointTransaction::where('user_id', $userId)->latest()->paginate(10, ['*'], 'credit_page');
    }

    /**
     * Everything the "service credit" part of the rewards page needs, with prices in the user's currency.
     *
     * @return array{tiers: array, quickPackages: array, transactions: mixed, currency: string}
     */
    public function creditPageFor(User $user): array
    {
        [$currencyCode, $rate] = $this->egpRateFor($user);

        $tiers = array_map(fn (array $tier) => [...$tier, 'price_per_point' => $tier['price_per_point'] * $rate], $this->getTiers());

        $quickPackages = array_map(fn (array $pkg) => [
            ...$pkg,
            'full_price' => $pkg['full_price'] * $rate,
            'total_cost' => $pkg['total_cost'] * $rate,
            'price_per_point' => $pkg['price_per_point'] * $rate,
            'savings' => $pkg['savings'] * $rate,
        ], $this->getQuickPackages());

        return [
            'tiers' => $tiers,
            'quickPackages' => $quickPackages,
            'transactions' => $this->getUserTransactions($user->id),
            'currency' => $currencyCode,
        ];
    }

    /**
     * The user's currency and the EGP rate for today. With no rate today, prices stay in EGP
     * instead of failing the page.
     *
     * @return array{0: string, 1: float}
     */
    private function egpRateFor(User $user): array
    {
        $egpId = Currency::where('currency', 'EGP')->value('id');
        $userCurrency = $user->currency ? Currency::find($user->currency) : null;

        if (! $egpId || ! $userCurrency || (int) $egpId === (int) $userCurrency->id) {
            return [$userCurrency?->currency ?? 'EGP', 1.0];
        }

        try {
            return [$userCurrency->currency, (float) CurrenciesExchange::RateToday(1, $egpId, $userCurrency->id)];
        } catch (\App\Exceptions\MissingExchangeRateException) {
            return ['EGP', 1.0];
        }
    }

    /**
     * Calculate cost based on custom tiers.
     */
    public function calculateCost(int $points): float
    {
        $pricePerPoint = 1.00;
        foreach ($this->tiers as $tier) {
            if ($points >= $tier['min'] && ($tier['max'] === null || $points <= $tier['max'])) {
                $pricePerPoint = $tier['price_per_point'];
                break;
            }
        }

        return (float) ($points * $pricePerPoint);
    }

    /**
     * Process a wallet payment using user balance.
     *
     * @throws \Exception
     */
    public function processWalletPayment(User $user, int $points, float $costInEgp): void
    {
        // Find currency ID for EGP
        $egpCurrency = Currency::where('currency', 'EGP')->first();
        $currencyId = $egpCurrency ? $egpCurrency->id : null;

        // Calculate cost in User's native currency
        $costInUserCurrency = CurrenciesExchange::RateToday($costInEgp, $currencyId, $user->currency);

        // If insufficient balance, throw exception
        if ((float) $user->user_balance < $costInUserCurrency) {
            throw new \Exception('INSUFFICIENT_FUNDS');
        }

        $this->executeInTransaction(function () use ($user, $points, $costInEgp, $currencyId) {
            // Deduct using built-in system (negative value to deduct)
            $user->add_balance(-$costInEgp, 'purchased_points', 'used', $currencyId);

            // Add points to user account
            $user->points_balance = ($user->points_balance ?? 0) + $points;
            $user->save();

            // Log point transaction
            $this->logPointTransaction($user->id, $points);
        });
    }

    /**
     * Log the point purchase in the database.
     */
    protected function logPointTransaction(int $userId, int $points): void
    {
        PointTransaction::create([
            'user_id' => $userId,
            'points' => $points,
            'type' => 'purchased',
        ]);
    }

    /**
     * Process a points purchase from a webhook payment.
     */
    public function processWebhookPurchase(User $user, float $amountPaid, string $reason, int $points, $packageId = null): void
    {
        $this->executeInTransaction(function () use ($user, $amountPaid, $reason, $points) {
            $user->add_balance($amountPaid, $reason, 'received');

            // Deduct balance for points
            $user->add_balance(-$amountPaid, 'Purchased '.$points.' points via Kashier', 'used');

            // Add points
            $user->points_balance = ($user->points_balance ?? 0) + $points;
            $user->save();

            // Log point transaction
            $this->logPointTransaction($user->id, $points);
        });
    }

    /**
     * Convert an EGP amount to user's currency.
     */
    public function getUserAmountAndCurrency(User $user, float $amountInEgp): array
    {
        $egpCurrency = Currency::where('currency', 'EGP')->first();
        $userCurrencyId = $user->currency;
        $userCurrency = Currency::find($userCurrencyId);

        $currencyCode = $userCurrency ? $userCurrency->currency : 'EGP';
        $rate = 1.0;

        if ($egpCurrency && $userCurrencyId && $egpCurrency->id != $userCurrencyId) {
            $rate = CurrenciesExchange::RateToday(1, $egpCurrency->id, $userCurrencyId);
        }

        return [
            'amount' => round($amountInEgp * $rate, 2),
            'currency' => $currencyCode,
        ];
    }
}
