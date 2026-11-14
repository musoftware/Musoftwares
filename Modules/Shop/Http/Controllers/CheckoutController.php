<?php

namespace Modules\Shop\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Modules\Shop\Http\Requests\CheckoutRequest;
use Modules\Shop\Models\ShopOrder;
use Modules\Shop\Services\Cart;
use Modules\Shop\Services\CheckoutCaptcha;
use Modules\Shop\Services\OrderFlood;
use Modules\Shop\Services\PlaceOrder;
use Modules\Shop\Services\ShopSeo;

class CheckoutController extends Controller
{
    public function __construct(private readonly Cart $cart) {}

    public function show(ShopSeo $seo): View|RedirectResponse
    {
        $lines = $this->cart->lines();
        if ($lines->isEmpty()) {
            return redirect()->route('shop.cart');
        }

        $subtotal = $this->cart->subtotal($lines);
        $shipping = $this->cart->shipping($lines);

        return view('shop::checkout', [
            'seo' => $seo->privatePage('إتمام الطلب'),
            'lines' => $lines,
            'subtotal' => $subtotal,
            'shipping' => $shipping,
            'total' => $subtotal + $shipping,
            'governorates' => $this->cart->governorates($lines),
            'areas' => $this->cart->areas($lines),
            'captchaKey' => app(CheckoutCaptcha::class)->siteKey(),
        ]);
    }

    public function store(CheckoutRequest $request, PlaceOrder $placeOrder, OrderFlood $flood): RedirectResponse
    {
        $lines = $this->cart->lines();
        if ($lines->isEmpty()) {
            return redirect()->route('shop.cart')->with('status', 'السلة فاضية، أو المنتجات اللي فيها خلصت');
        }
        if (! $flood->allows((string) $request->validated('phone'))) {
            return back()->withInput()->withErrors(['phone' => 'وصلتنا طلباتك من الرقم ده وهنتواصل معاك لتأكيدها. لو محتاج طلب تاني جرّب بعد شوية.']);
        }

        $order = $placeOrder->handle($request->customer(), $lines);

        return redirect()->route('shop.thanks', $order->uuid);
    }

    public function thanks(ShopSeo $seo, string $uuid): View
    {
        return view('shop::thanks', [
            'seo' => $seo->privatePage('تم استلام طلبك'),
            'order' => ShopOrder::where('uuid', $uuid)->firstOrFail(),
        ]);
    }
}
