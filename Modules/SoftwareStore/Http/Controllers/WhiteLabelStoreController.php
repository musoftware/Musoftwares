<?php

namespace Modules\SoftwareStore\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Modules\SoftwareStore\Models\SoftwareProduct;
use Modules\SoftwareStore\Models\WhiteLabelPartner;
use Modules\SoftwareStore\Services\SoftwareOrderService;
use Modules\SoftwareStore\Services\WhiteLabelService;

/**
 * A partner's own store: their name, logo and prices, none of ours. The list page can be framed
 * on the partner's site (?embed=1); checkout always opens in its own tab so the session cookie and
 * the payment page work.
 */
class WhiteLabelStoreController extends Controller
{
    public function __construct(private readonly WhiteLabelService $whiteLabel, private readonly SoftwareOrderService $orders) {}

    public function index(Request $request, WhiteLabelPartner $partner)
    {
        abort_unless($partner->isActive(), 404);

        $products = $this->whiteLabel->products()->map(fn (SoftwareProduct $product) => [
            'product' => $product,
            'price' => $product->hasPlans()
                ? min(array_map(fn ($plan) => $this->whiteLabel->partnerPrice($partner, $product, $plan['key']), $product->planList()))
                : $this->whiteLabel->partnerPrice($partner, $product),
        ]);

        return view('softwarestore::white-label.store', [
            'partner' => $partner,
            'products' => $products,
            'embed' => $request->boolean('embed'),
        ]);
    }

    public function show(WhiteLabelPartner $partner, string $slug)
    {
        abort_unless($partner->isActive(), 404);
        $product = $this->sellable($slug);

        $plans = array_map(fn ($plan) => $plan + ['partner_price' => $this->whiteLabel->partnerPrice($partner, $product, $plan['key'])], $product->planList());

        return view('softwarestore::white-label.product', [
            'partner' => $partner,
            'product' => $product,
            'plans' => $plans,
            'price' => $this->whiteLabel->partnerPrice($partner, $product),
        ]);
    }

    public function order(Request $request, WhiteLabelPartner $partner, string $slug)
    {
        abort_unless($partner->isActive(), 404);
        $product = $this->sellable($slug);

        $client = $request->validate([
            'client_name' => 'required|string|max:255',
            'client_email' => 'required|email|max:255',
            'client_whatsapp' => 'required|string|max:50',
            'plan_key' => $product->hasPlans() ? ['required', Rule::in(array_column($product->planList(), 'key'))] : 'nullable',
            'device_id' => ['nullable', 'string', 'max:128', 'regex:/^[A-Za-z0-9_\- ]+$/'],
        ]);
        $client['device_id'] = isset($client['device_id']) ? preg_replace('/\s+/', '', $client['device_id']) : null;

        $order = $this->orders->createPendingOrder($product, $client, $partner);

        try {
            return redirect()->away($this->orders->paymentUrl($order));
        } catch (\Throwable $e) {
            Log::error('White label order: payment start failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);

            return back()->withInput()->with('error', __('software.payment_start_failed'));
        }
    }

    private function sellable(string $slug): SoftwareProduct
    {
        return $this->whiteLabel->products()->firstWhere('slug', $slug) ?? abort(404);
    }
}
