<?php

namespace Modules\Marketplace\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Marketplace\Services\PromotionsService;

class PromotionsController extends Controller
{
    public function __construct(protected PromotionsService $promotionsService) {}

    public function applyCoupon(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
        ]);

        try {
            $res = $this->promotionsService->applyCoupon(
                $validated['code'],
                (float) $validated['amount'],
                auth()->user()
            );

            return response()->json(['success' => true, 'coupon' => $res]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
        }
    }
}
