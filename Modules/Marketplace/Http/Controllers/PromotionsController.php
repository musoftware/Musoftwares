<?php

namespace Modules\Marketplace\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Marketplace\Services\PromotionsService;

class PromotionsController extends Controller
{
    public function __construct(protected PromotionsService $promotionsService) {}

    public function applyCoupon(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
        ]);

        try {
            $res = $this->promotionsService->applyCoupon(
                $validated['code'],
                (float) $validated['amount'],
                $request->user()
            );

            if ($request->header('X-Inertia') || ! $request->wantsJson()) {
                return back()->with('success', __('general.coupon_applied_successfully'));
            }

            return response()->json(['success' => true, 'coupon' => $res]);
        } catch (\Exception $e) {
            if ($request->header('X-Inertia') || ! $request->wantsJson()) {
                return back()->withErrors(['coupon' => $e->getMessage()]);
            }

            return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
        }
    }
}

