<?php

namespace Modules\Marketplace\Services;

use App\Builders\KashierCheckoutBuilder;
use App\Models\CurrenciesExchange;
use App\Models\User;
use Illuminate\Support\Facades\Session;
use Modules\Marketplace\Models\ServicePackage;

/**
 * Orders are paid from the wallet. A buyer without enough balance pays the missing amount at Kashier,
 * through the normal wallet top-up webhook, and then returns to finish the order they started.
 */
class MarketplaceOrderPayment
{
    private const PENDING_KEY = 'marketplace.pending_package_id';

    private const MIN_TOP_UP = 5.0;

    public function priceIn(ServicePackage $package, $currencyId): float
    {
        if (! $package->currency_id || ! $currencyId || (int) $package->currency_id === (int) $currencyId) {
            return (float) $package->price;
        }

        return (float) CurrenciesExchange::RateToday($package->price, $package->currency_id, $currencyId);
    }

    /** How much more the buyer needs; zero or less means the wallet is enough. */
    public function shortfall(User $buyer, ServicePackage $package): float
    {
        return $this->priceIn($package, $buyer->currency_id) - (float) $buyer->available_balance();
    }

    public function startCheckout(User $buyer, ServicePackage $package): string
    {
        Session::put(self::PENDING_KEY, $package->id);

        return KashierCheckoutBuilder::make()
            ->forAmount($this->topUpAmount($buyer, $package), $buyer->currency_name())
            ->forUser($buyer->id, $buyer->name, $buyer->email)
            ->withSource('balance-recharge', 'deposit_')
            ->withRoutes(
                success: route('marketplace.orders.resume'),
                failure: route('marketplace.orders.resume-failed'),
                webhook: route('financial.add-balance.webhook'),
            )
            ->build();
    }

    public function pendingPackage(): ?ServicePackage
    {
        return ServicePackage::with('service')->find(Session::get(self::PENDING_KEY));
    }

    public function forgetPending(): void
    {
        Session::forget(self::PENDING_KEY);
    }

    private function topUpAmount(User $buyer, ServicePackage $package): float
    {
        return ceil(max($this->shortfall($buyer, $package), self::MIN_TOP_UP) * 100) / 100;
    }
}
