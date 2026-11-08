<?php

namespace Modules\Marketplace\Services;

use Modules\Marketplace\Models\MarketplaceEscrow;
use Modules\Marketplace\Models\ServiceOrder;
use Modules\Marketplace\Enums\EscrowStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use App\Models\CurrenciesExchange;
use App\Models\AdminSettings;

class EscrowService
{
    /**
     * Locks funds from the buyer's wallet and creates an Escrow record.
     */
    public function holdFunds(ServiceOrder $order): MarketplaceEscrow
    {
        return DB::transaction(function () use ($order) {
            $buyer = $order->buyer;
            
            // The balance is in the buyer's currency, so the price is compared in that same currency.
            if ($buyer->available_balance() < app(MarketplaceOrderPayment::class)->priceIn($order->package, $buyer->currency_id)) {
                throw new Exception(__('marketplace.insufficient_balance_for_escrow'));
            }

            // Deduct from Buyer using 'used' type per rules
            $transactionId = $buyer->add_balance(
                -$order->amount,
                __('marketplace.escrow_hold_description', ['id' => $order->id]),
                'used',
                $order->currency_id
            );

            $businessCurrencyStr = CurrenciesExchange::BusinessCurrency();
            $businessCurrencyModel = \App\Models\Currency::where('currency', $businessCurrencyStr)->first();
            $businessCurrencyId = $businessCurrencyModel ? $businessCurrencyModel->id : $order->currency_id;

            $currencyStr = \App\Models\Currency::find($order->currency_id)->currency ?? '';

            $exchangeRate = CurrenciesExchange::RateByDate(now('Africa/Cairo')->toDateString(), 1, $currencyStr, $businessCurrencyStr);
            // Ensure proper normalized business amount for the entire order
            $businessAmount = $order->amount * $exchangeRate;

            $escrow = MarketplaceEscrow::create([
                'order_id' => $order->id,
                'buyer_wallet_transaction_id' => $transactionId,
                'amount' => $order->amount,
                'currency_id' => $order->currency_id,
                'business_amount' => $businessAmount,
                'business_currency_id' => $businessCurrencyId,
                'exchange_rate' => $exchangeRate,
                'exchange_rate_date' => now('Africa/Cairo'),
                'status' => EscrowStatus::HELD
            ]);

            return $escrow;
        });
    }

    /**
     * Releases funds to the seller, deducting the system commission.
     */
    public function releaseFunds(MarketplaceEscrow $escrow): void
    {
        DB::transaction(function () use ($escrow) {
            if (!in_array($escrow->status, [EscrowStatus::HELD, EscrowStatus::DISPUTED])) {
                throw new Exception(__('marketplace.escrow_cannot_release'));
            }

            $order = $escrow->order;
            $seller = $order->seller;
            
            // The status check above runs once per escrow, so the marketer is paid only once.
            $sellerEarnings = $order->amount - $order->commission_amount - $this->payMarketer($order);

            $transactionId = $seller->add_balance(
                $sellerEarnings,
                __('marketplace.escrow_released_description', ['id' => $order->id]),
                'earned',
                $order->currency_id
            );

            $escrow->update([
                'status' => EscrowStatus::RELEASED,
                'seller_wallet_transaction_id' => $transactionId,
                'released_at' => now('Africa/Cairo'),
            ]);
        });
    }

    /**
     * Pays the marketer who brought the buyer. Returns what comes out of the seller's share:
     * the marketer's cut when the service says the seller pays it, otherwise nothing.
     * A failure here is logged and must not hold back the seller's money.
     */
    private function payMarketer(ServiceOrder $order): float
    {
        try {
            $paid = (float) app(ReferralService::class)->processOrderReferralCommission($order);
        } catch (\Throwable $e) {
            Log::error('Marketplace referral commission failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);

            return 0.0;
        }

        return $order->package?->service?->referral_commission_from === 'seller' ? $paid : 0.0;
    }

    /**
     * Refunds the escrowed funds back to the buyer.
     */
    public function refundFunds(MarketplaceEscrow $escrow): void
    {
        DB::transaction(function () use ($escrow) {
            if (!in_array($escrow->status, [EscrowStatus::HELD, EscrowStatus::DISPUTED])) {
                throw new Exception(__('marketplace.escrow_cannot_refund'));
            }

            $order = $escrow->order;
            $buyer = $order->buyer;

            // Credit back buyer via 'refunded'
            $transactionId = $buyer->add_balance(
                $order->amount,
                __('marketplace.escrow_refunded_description', ['id' => $order->id]),
                'refunded',
                $order->currency_id
            );

            $escrow->update([
                'status' => EscrowStatus::REFUNDED,
                'refunded_at' => now('Africa/Cairo'),
            ]);
        });
    }

    public function dispute(MarketplaceEscrow $escrow): void
    {
        DB::transaction(function () use ($escrow) {
            if ($escrow->status !== EscrowStatus::HELD) {
                throw new Exception(__('marketplace.escrow_dispute_held_only'));
            }

            $escrow->update([
                'status' => EscrowStatus::DISPUTED
            ]);
        });
    }
}
