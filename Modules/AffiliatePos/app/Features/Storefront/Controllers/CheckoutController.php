<?php

namespace Modules\AffiliatePos\app\Features\Storefront\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\AffiliatePos\Models\Order;
use Modules\AffiliatePos\Models\OrderItem;
use Modules\AffiliatePos\app\Features\Storefront\Requests\CheckoutRequest;
use Modules\AffiliatePos\app\Features\Storefront\Services\CartService;
use Modules\AffiliatePos\app\Features\OrderManagement\Resources\OrderResource;

class CheckoutController extends Controller
{
    private $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    private function resolveSessionId($request)
    {
        return Auth::check() ? null : $request->cookie('cart_session');
    }

    public function process(CheckoutRequest $request)
    {
        $cart = $this->cartService->getCart(Auth::id(), $this->resolveSessionId($request));

        if ($cart->items->isEmpty()) {
            return response()->json(['message' => 'Cart is empty'], 400);
        }

        $order = DB::transaction(function () use ($request, $cart) {
            $uniqueId = Str::random(10);
            
            // For now, delivery cost is mocked or would come from Governorate relation.
            // Assuming delivery is 0 or passed from governorate calculation.
            $delivery = 0; 
            
            $subtotal = 0;
            $totalCommission = 0;

            foreach ($cart->items as $item) {
                $subtotal += ($item->price * $item->qty);
                $totalCommission += ($item->commission * $item->qty);
            }

            $order = Order::create([
                'tenant_id' => $cart->tenant_id,
                'user_id' => Auth::id(), // Affiliate creating the order
                'customer_name' => $request->customer_name,
                'customer_phone' => $request->customer_phone,
                'customer_phone2' => $request->customer_phone2,
                'customer_email' => $request->customer_email,
                'customer_address' => $request->customer_address,
                'customer_city_id' => $request->customer_city_id,
                'customer_governorate_id' => $request->customer_governorate_id,
                'note_value' => $request->note_value,
                'order_date' => now(),
                'subtotal' => $subtotal,
                'commission' => $totalCommission,
                'delivery' => $delivery,
                'total' => $subtotal + $delivery,
                'status' => 'new',
                'unique_id' => $uniqueId,
            ]);

            foreach ($cart->items as $item) {
                OrderItem::create([
                    'tenant_id' => $order->tenant_id,
                    'order_id' => $order->id,
                    'user_id' => Auth::id(), // Affiliate
                    'product_id' => $item->product_id,
                    'sku_id' => $item->sku_id,
                    'price' => $item->price,
                    'commission' => $item->commission,
                    'website_commission_type' => $item->website_commission_type ?? 'fixed',
                    'website_commission' => $item->website_commission ?? 0,
                    'qty' => $item->qty,
                    'total' => $item->price * $item->qty,
                    'total_commission' => $item->commission * $item->qty,
                    'status' => 'new',
                ]);
                
                // Decrease stock
                if ($item->sku) {
                    $item->sku->decreaseStock($item->qty, [
                        'reference_type' => Order::class,
                        'reference_id' => $order->id,
                        'description' => "Stock consumed for order {$uniqueId}"
                    ]);
                }
            }

            $this->cartService->clearCart($cart);

            return $order;
        });

        return new OrderResource($order->load('items.product', 'items.sku'));
    }
}
