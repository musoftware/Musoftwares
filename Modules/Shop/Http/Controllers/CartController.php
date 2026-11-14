<?php

namespace Modules\Shop\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Shop\Models\ShopProduct;
use Modules\Shop\Services\Cart;
use Modules\Shop\Services\ShopSeo;

class CartController extends Controller
{
    public function __construct(private readonly Cart $cart) {}

    public function show(ShopSeo $seo): View
    {
        $lines = $this->cart->lines();

        return view('shop::cart', [
            'seo' => $seo->privatePage('سلة المشتريات'),
            'lines' => $lines,
            'subtotal' => $this->cart->subtotal($lines),
        ]);
    }

    public function add(Request $request, ShopProduct $product): RedirectResponse
    {
        abort_unless($product->canBeOrdered(), 404);

        $data = $request->validate($this->rules($product), [
            'size.required' => 'اختار المقاس الأول',
            'color.required' => 'اختار اللون الأول',
        ]);

        if (! $product->canSupply($data['size'] ?? null, $data['color'] ?? null)) {
            return back()->withInput()->withErrors([($product->sizes ?? []) === [] ? 'color' : 'size' => 'الاختيار ده خلص، جرب مقاس أو لون تاني']);
        }

        $this->cart->add($product, (int) $data['quantity'], $data['size'] ?? null, $data['color'] ?? null);

        return $request->boolean('buy_now') ? redirect()->route('shop.checkout') : $this->backToCart($request, 'تمت إضافة المنتج للسلة');
    }

    public function update(Request $request, string $key): RedirectResponse
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:1', 'max:'.config('shop.max_quantity')]]);
        $this->cart->setQuantity($key, (int) $data['quantity']);

        return $this->backToCart($request);
    }

    /** Another size or colour for a line already in the cart. */
    public function options(Request $request, string $key): RedirectResponse
    {
        $product = $this->cart->productOf($key);
        if ($product === null) {
            return $this->backToCart($request);
        }
        $data = $request->validate(['size' => $this->optionRules($product->sizes ?? []), 'color' => $this->optionRules($product->colors ?? [])]);
        if (! $product->canSupply($data['size'] ?? null, $data['color'] ?? null)) {
            return $this->backToCart($request, 'الاختيار ده خلص، جرب مقاس أو لون تاني');
        }
        $this->cart->changeOptions($key, $data['size'] ?? null, $data['color'] ?? null);

        return $this->backToCart($request);
    }

    public function remove(Request $request, string $key): RedirectResponse
    {
        $this->cart->remove($key);

        return $this->backToCart($request);
    }

    /** From the cart panel the customer stays on their page with the panel open; otherwise they go to the cart page. */
    private function backToCart(Request $request, ?string $status = null): RedirectResponse
    {
        $response = $request->boolean('panel') ? back()->with('cart_open', true) : redirect()->route('shop.cart');

        return $status === null ? $response : $response->with('status', $status);
    }

    private function rules(ShopProduct $product): array
    {
        return [
            'quantity' => ['required', 'integer', 'min:1', 'max:'.config('shop.max_quantity')],
            'size' => $this->optionRules($product->sizes ?? []),
            'color' => $this->optionRules($product->colors ?? []),
        ];
    }

    /** The customer must pick an option only when the product has options. */
    private function optionRules(array $options): array
    {
        return $options === [] ? ['prohibited'] : ['required', 'string', Rule::in($options)];
    }
}
