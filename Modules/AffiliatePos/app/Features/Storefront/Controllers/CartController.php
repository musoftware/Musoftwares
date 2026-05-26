<?php

namespace Modules\AffiliatePos\app\Features\Storefront\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Modules\AffiliatePos\app\Features\Storefront\Requests\AddToCartRequest;
use Modules\AffiliatePos\app\Features\Storefront\Requests\UpdateCartItemRequest;
use Modules\AffiliatePos\app\Features\Storefront\Resources\CartResource;
use Modules\AffiliatePos\app\Features\Storefront\Services\CartService;

class CartController extends Controller
{
    private $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    private function resolveSessionId(Request $request)
    {
        if (!Auth::check() && !$request->cookie('cart_session')) {
            $sessionId = Str::uuid()->toString();
            cookie()->queue('cart_session', $sessionId, 60 * 24 * 30);
            return $sessionId;
        }
        return $request->cookie('cart_session');
    }

    public function index(Request $request)
    {
        $cart = $this->cartService->getCart(Auth::id(), $this->resolveSessionId($request));
        return new CartResource($cart);
    }

    public function add(AddToCartRequest $request)
    {
        $cart = $this->cartService->getCart(Auth::id(), $this->resolveSessionId($request));
        $this->cartService->addItem($cart, $request->sku_id, $request->quantity);
        
        return new CartResource($cart);
    }

    public function update(UpdateCartItemRequest $request, $itemId)
    {
        $cart = $this->cartService->getCart(Auth::id(), $this->resolveSessionId($request));
        $this->cartService->updateItem($cart, $itemId, $request->quantity);
        
        return new CartResource($cart);
    }

    public function updateCommission(Request $request, $itemId)
    {
        $request->validate([
            'commission' => 'required|numeric|min:0'
        ]);

        $cart = $this->cartService->getCart(Auth::id(), $this->resolveSessionId($request));
        $this->cartService->updateCommission($cart, $itemId, $request->commission);
        
        return new CartResource($cart);
    }

    public function remove(Request $request, $itemId)
    {
        $cart = $this->cartService->getCart(Auth::id(), $this->resolveSessionId($request));
        $this->cartService->removeItem($cart, $itemId);
        
        return new CartResource($cart);
    }
}
