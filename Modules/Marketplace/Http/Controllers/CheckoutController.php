<?php

namespace Modules\Marketplace\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Marketplace\Services\CheckoutService;

class CheckoutController extends Controller
{
    public function __construct(protected CheckoutService $checkoutService) {}

    public function process(Request $request)
    {
        $validated = $request->validate([
            'package_id' => 'required|exists:marketplace_packages,id',
            'coupon_code' => 'nullable|string',
            'extra_ids' => 'nullable|array',
            'extra_ids.*' => 'exists:marketplace_service_extras,id',
        ]);

        try {
            $order = $this->checkoutService->processCheckout(
                auth()->user(),
                (int) $validated['package_id'],
                $validated['coupon_code'] ?? null,
                $validated['extra_ids'] ?? []
            );

            return response()->json(['success' => true, 'order_id' => $order->id]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
        }
    }
}
