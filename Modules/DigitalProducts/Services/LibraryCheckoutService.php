<?php

namespace Modules\DigitalProducts\Services;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\DigitalProducts\Models\DigitalProduct;
use Modules\DigitalProducts\Models\DigitalProductPurchase;
use Exception;

class LibraryCheckoutService
{
    /**
     * Purchase a digital book using user's wallet balance.
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

            $price = (float) $product->price;

            if ($price > 0) {
                $availableBalance = $lockedUser->available_balance();
                if ($availableBalance < $price) {
                    throw new Exception(__('digitalproducts.insufficient_wallet_balance', [
                        'required' => number_format($price, 2),
                        'available' => number_format($availableBalance, 2),
                    ]));
                }

                $currencyCode = $lockedUser->currency_name() ?? 'USD';
                $description = "شراء كتاب رقمي: {$product->title}";

                // Deduct balance
                $lockedUser->add_balance(-$price, $description, 'used', $lockedUser->currency_id ?? $product->currency_id);

                // Record financial transaction
                Transaction::create([
                    'user_id' => $lockedUser->id,
                    'type' => 'payment',
                    'amount' => $price,
                    'currency' => $currencyCode,
                    'description' => $description,
                ]);
            }

            $purchase = DigitalProductPurchase::create([
                'user_id' => $lockedUser->id,
                'digital_product_id' => $product->id,
                'amount_paid' => $price,
                'currency_id' => $product->currency_id ?? 1,
                'payment_method' => 'wallet',
                'status' => 'completed',
            ]);

            $product->increment('download_count');

            return $purchase;
        });
    }
}
