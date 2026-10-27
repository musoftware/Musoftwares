<?php

namespace Modules\SoftwareStore\Services;

use App\Models\CurrenciesExchange;
use App\Models\Currency;
use App\Models\Earning;
use App\Models\User;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\SoftwareStore\Models\SoftwareOrder;
use Modules\SoftwareStore\Models\SoftwareProduct;
use Modules\SoftwareStore\Models\WhiteLabelPartner;
use RuntimeException;

/**
 * The money rules of white label:
 * - the partner pays a monthly subscription from their wallet;
 * - they sell at our price or higher, never lower;
 * - on every paid order they earn 30% of our price plus everything they added on top.
 */
class WhiteLabelService
{
    public const MONTHLY_PRICE = 1000;

    public const CURRENCY = 'EGP';

    public const SUBSCRIPTION_DAYS = 30;

    public const COMMISSION_PERCENT = 30;

    /** Days a share waits before it moves to the wallet, so a refunded payment can still be taken back. */
    public const CLEARING_DAYS = 14;

    /** The programs a partner can sell: active, priced, and not a business system or a service. */
    public function products(): Collection
    {
        return SoftwareProduct::where('is_active', true)
            ->with('serialSoftware')
            ->orderBy('id')
            ->get()
            ->filter(fn (SoftwareProduct $product) => $product->group() === 'programs' && $product->startingPrice() > 0)
            ->values();
    }

    public static function priceKey(SoftwareProduct $product, ?string $planKey): string
    {
        return $planKey ? $product->id.':'.$planKey : (string) $product->id;
    }

    /** The price the partner's customer pays. A price under ours (or none) falls back to ours. */
    public function partnerPrice(WhiteLabelPartner $partner, SoftwareProduct $product, ?string $planKey = null): float
    {
        $base = $product->salePrice($planKey);
        $set = (float) ($partner->prices[self::priceKey($product, $planKey)] ?? 0);

        return round(max($base, $set), 2);
    }

    /** 30% of our price plus the partner's markup. */
    public function commission(float $base, float $partnerPrice): float
    {
        return round($base * self::COMMISSION_PERCENT / 100 + max(0, $partnerPrice - $base), 2);
    }

    /**
     * Charges one month to the partner's wallet and adds 30 days on top of the current end date
     * (or today when it has ended). Throws DomainException when the wallet does not hold enough.
     */
    public function subscribe(WhiteLabelPartner $partner): void
    {
        DB::transaction(function () use ($partner) {
            /** @var User $user */
            $user = User::whereKey($partner->user_id)->lockForUpdate()->firstOrFail();
            $currencyId = Currency::where('currency', self::CURRENCY)->value('id');
            if (! $currencyId) {
                throw new RuntimeException('Currency '.self::CURRENCY.' is missing');
            }

            $price = (float) CurrenciesExchange::RateToday(self::MONTHLY_PRICE, $currencyId, $user->currency_id);
            if ((float) $user->user_balance < $price) {
                throw new DomainException(__('white_label.not_enough_balance'));
            }

            $user->add_balance(-1 * self::MONTHLY_PRICE, 'White label subscription', 'used', $currencyId);

            $from = $partner->isActive() ? $partner->subscription_expires_at->copy() : now();
            $partner->update(['subscription_expires_at' => $from->addDays(self::SUBSCRIPTION_DAYS)]);
        });
    }

    /** Records the partner's share of a paid order once. A failure is logged and never undoes the payment. */
    public function recordCommission(SoftwareOrder $order): void
    {
        $partner = $order->partner;
        $amount = (float) $order->partner_commission;
        if (! $partner || $amount <= 0) {
            return;
        }

        try {
            $currencyId = Currency::where('currency', $order->currency)->value('id');
            Earning::create([
                'user_id' => $partner->user_id,
                'currency_id' => $currencyId,
                'amount' => $amount,
                'convert_to_balance_on' => now()->addDays(self::CLEARING_DAYS),
            ]);
        } catch (\Throwable $e) {
            Log::error('White label: commission not recorded', ['order_id' => $order->id, 'error' => $e->getMessage()]);
        }
    }

    /** The snippet a partner pastes into their own site. */
    public function iframeCode(WhiteLabelPartner $partner): string
    {
        return '<iframe src="'.route('white-label.store', ['partner' => $partner, 'embed' => 1]).'" style="width:100%;min-height:720px;border:0" loading="lazy" title="'.e($partner->brand_name).'"></iframe>';
    }
}
