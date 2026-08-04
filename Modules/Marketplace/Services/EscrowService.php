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
                throw new Exception("رصيدك الحسابي غير كافٍ لحجز مبلغ الضمان (Escrow).");
            }

            // Deduct from Buyer using 'used' type per rules
            $transactionId = $buyer->add_balance(
                -$order->amount,
                "Escrow hold for service order #{$order->id}",
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
                throw new Exception("حالة مبلغ الضمان الحالي لا تسمح بتحرير الأموال للمستفيد.");
            }

            $order = $escrow->order;
            $seller = $order->seller;
            
            // Re-calculate or use stored commission
            $sellerEarnings = $order->amount - $order->commission_amount;

            $transactionId = $seller->add_balance(
                $sellerEarnings,
                "Earnings from service order #{$order->id} (Escrow Released)",
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
                throw new Exception("تعذر إرجاع مبلغ الضمان للمشتري في الحالة الحالية.");
            }

            $order = $escrow->order;
            $buyer = $order->buyer;

            // Credit back buyer via 'refunded'
            $transactionId = $buyer->add_balance(
                $order->amount,
                "Refund for service order #{$order->id} (Escrow Cancelled)",
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
                throw new Exception("يمكن فتح النزاع فقط للمبالغ المحجوزة قيد التنفيذ.");
            }

            $escrow->update([
                'status' => EscrowStatus::DISPUTED
            ]);
        });
    }
}
