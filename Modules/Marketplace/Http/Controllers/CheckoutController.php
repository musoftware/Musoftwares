<?php

namespace Modules\Marketplace\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Marketplace\Services\CheckoutService;

class CheckoutController extends Controller
{
    public function __construct(protected CheckoutService $checkoutService) {}

    public function process(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'package_id' => 'required|exists:marketplace_packages,id',
            'coupon_code' => 'nullable|string',
            'extra_ids' => 'nullable|array',
            'extra_ids.*' => 'exists:marketplace_service_extras,id',
        ]);

        try {
            $order = $this->checkoutService->processCheckout(
                $request->user(),
                (int) $validated['package_id'],
                $validated['coupon_code'] ?? null,
                $validated['extra_ids'] ?? []
            );

            if ($request->header('X-Inertia') || ! $request->wantsJson()) {
                return redirect()->route('marketplace.orders.show', $order->id)
                    ->with('success', __('general.order_placed_successfully'));
            }

            return response()->json(['success' => true, 'order_id' => $order->id]);
        } catch (\Exception $e) {
            if ($request->header('X-Inertia') || ! $request->wantsJson()) {
                return back()->withErrors(['error' => $e->getMessage()]);
            }

            return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
        }
    }
}

