<?php

namespace Modules\Marketplace\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Marketplace\Services\ReferralService;

class ReferralController extends Controller
{
    public function __construct(protected ReferralService $referralService) {}

    public function withdraw(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:10',
            'payout_method' => 'required|string',
            'payment_details' => 'required|array',
        ]);

        try {
            $withdrawRequest = $this->referralService->requestWithdrawal(
                auth()->user(),
                (float) $validated['amount'],
                $validated['payout_method'],
                $validated['payment_details']
            );

            if ($request->header('X-Inertia') || !$request->wantsJson()) {
                return back()->with('success', __('general.withdrawal_requested_successfully'));
            }

            return response()->json(['success' => true, 'request' => $withdrawRequest]);
        } catch (\Exception $e) {
            if ($request->header('X-Inertia') || !$request->wantsJson()) {
                return back()->withErrors(['withdrawal' => $e->getMessage()]);
            }

            return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
        }
    }
}
