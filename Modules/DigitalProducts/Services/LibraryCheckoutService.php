<?php

namespace Modules\DigitalProducts\Services;

use App\Helpers\FinanceHelper;
use App\Models\CurrenciesExchange;
use App\Models\Currency;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\DigitalProducts\Models\DigitalProduct;
use Modules\DigitalProducts\Models\DigitalProductPurchase;
use Exception;

class LibraryCheckoutService
{
    /**
     * Purchase a digital book using user's wallet balance with dynamic currency conversion.
     */
    public function purchaseWithWallet(User $user, DigitalProduct $product): DigitalProductPurchase
    {
        return DB::transaction(function () use ($user, $product) {
            // Lock user for update to prevent concurrent deductions
            $lockedUser = User::where('id', $user->id)->lockForUpdate()->firstOrFail();

            if ($product->isPurchasedBy($lockedUser)) {
                return DigitalProductPurchase::where('user_id', $lockedUser->id)
                    ->where('digital_product_id', $product->id)
                    ->first();
            }

            $basePrice = (float) $product->price;
            $productCurrencyId = (int) ($product->currency_id ?: 1);
            $userCurrencyId = (int) ($lockedUser->currency_id ?: 1);

            $costInUserCurrency = 0.0;
            $transactionId = null;

            if ($basePrice > 0) {
                // Convert book price to user's wallet currency
                $costInUserCurrency = $productCurrencyId === $userCurrencyId
                    ? $basePrice
                    : (float) CurrenciesExchange::RateToday($basePrice, $productCurrencyId, $userCurrencyId);

                $availableBalance = (float) $lockedUser->available_balance();

                if ($availableBalance < $costInUserCurrency) {
                    $formattedRequired = FinanceHelper::instance()->format_money($costInUserCurrency, $userCurrencyId);
                    $formattedAvailable = FinanceHelper::instance()->format_money($availableBalance, $userCurrencyId);

                    $msg = __('digitalproducts.insufficient_wallet_balance', [
                        'required' => $formattedRequired,
                        'available' => $formattedAvailable,
                    ]);

                    if (empty($msg) || $msg === 'digitalproducts.insufficient_wallet_balance') {
                        $msg = app()->getLocale() === 'ar'
                            ? "رصيد المحفظة غير كافٍ. المطلوب: {$formattedRequired}، المتوفر: {$formattedAvailable}."
                            : "Insufficient wallet balance. Required: {$formattedRequired}, Available: {$formattedAvailable}.";
                    }

                    throw new Exception($msg);
                }

                $description = "شراء كتاب رقمي: {$product->title}";

                // Deduct balance in user's currency (creates financial Transaction automatically)
                $transactionId = $lockedUser->add_balance(-$costInUserCurrency, $description, 'used', $userCurrencyId);
            }

            $purchase = DigitalProductPurchase::create([
                'user_id' => $lockedUser->id,
                'digital_product_id' => $product->id,
                'amount_paid' => $costInUserCurrency,
                'currency_id' => $userCurrencyId,
                'payment_method' => 'wallet',
                'transaction_id' => $transactionId ? (string) $transactionId : null,
                'status' => 'completed',
            ]);

            $product->increment('download_count');

            return $purchase;
        });
    }
}
