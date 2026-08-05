<?php

namespace Modules\Marketplace\Services;

use Modules\Marketplace\Models\MarketplaceEscrow;
use Modules\Marketplace\Models\ServiceOrder;
use Modules\Marketplace\Enums\EscrowStatus;
use Illuminate\Support\Facades\DB;
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
            
            if ($buyer->available_balance() < $order->amount) {
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
            
            // Re-calculate or use stored commission
            $sellerEarnings = $order->amount - $order->commission_amount;

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
