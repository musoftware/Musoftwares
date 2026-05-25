<?php

namespace Modules\AffiliatePos\app\Features\Storefront\Services;

use Modules\AffiliatePos\Models\Cart;
use Modules\AffiliatePos\Models\ProductSku;

class CartService
{
    public function getCart($userId, $sessionId)
    {
        if ($userId) {
            return Cart::with('items.product', 'items.sku')->firstOrCreate(['user_id' => $userId]);
        }
        return Cart::with('items.product', 'items.sku')->firstOrCreate(['session_id' => $sessionId]);
    }

    public function addItem(Cart $cart, $skuId, $quantity)
    {
        $sku = ProductSku::with('product')->findOrFail($skuId);
        $product = $sku->product;

        $qty = min($sku->stock(), $quantity);

        $cartItem = $cart->items()->where('sku_id', $skuId)->first();

        if ($cartItem) {
            $newQty = min($sku->stock(), $cartItem->qty + $qty);
            $cartItem->update([
                'qty' => $newQty
            ]);
        } else {
            $cart->items()->create([
                'product_id' => $product->id,
                'sku_id' => $sku->id,
                'qty' => $qty,
                'price' => $product->price,
                'commission' => $product->commission,
                'website_commission_type' => $product->website_commission_type ?? 'fixed',
                'website_commission' => $product->website_commission ?? 0,
            ]);
        }

        return $cart->load('items.product', 'items.sku');
    }

    public function updateItem(Cart $cart, $itemId, $quantity)
    {
        $cartItem = $cart->items()->findOrFail($itemId);
        $sku = $cartItem->sku;

        $qty = min($sku->stock(), $quantity);

        $cartItem->update(['qty' => $qty]);

        return $cart->load('items.product', 'items.sku');
    }

    public function updateCommission(Cart $cart, $itemId, $newCommission)
    {
        $cartItem = $cart->items()->with('product')->findOrFail($itemId);
        
        // Enforce minimum commission logic (Legacy rule: min_commission = product->commission / 2)
        $minCommission = max(round($cartItem->product->commission / 2), 0);
        $finalCommission = max($newCommission, $minCommission);

        $cartItem->update(['commission' => $finalCommission]);

        return $cart->load('items.product', 'items.sku');
    }

    public function removeItem(Cart $cart, $itemId)
    {
        $cart->items()->where('id', $itemId)->delete();
        return $cart->load('items.product', 'items.sku');
    }

    public function clearCart(Cart $cart)
    {
        $cart->items()->delete();
    }
}
