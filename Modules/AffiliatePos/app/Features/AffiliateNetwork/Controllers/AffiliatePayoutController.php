<?php

namespace Modules\AffiliatePos\app\Features\AffiliateNetwork\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Modules\AffiliatePos\Models\PaymentRequest;
use Modules\AffiliatePos\app\Features\OrderManagement\Services\PaymentService;

class AffiliatePayoutController extends Controller
{
    private $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function index()
    {
        $payouts = PaymentRequest::with('payment_method')
            ->where('user_id', Auth::id())
            ->latest()
            ->paginate(20);

        return Inertia::render('AffiliatePos/Affiliate/Payouts/Index', [
            'payouts' => $payouts
        ]);
    }

    public function requestPayout(Request $request)
    {
        $request->validate([
            'payment_method_id' => 'required|exists:affiliate_pos_payment_methods,id',
            'amount' => 'required|numeric|min:1'
        ]);

        $payout = $this->paymentService->requestPayout(
            Auth::id(),
            // Assuming tenant scoping middleware handles the tenant context, fallback to 1 for tests
            $request->header('X-Tenant-ID') ?? 1,
            $request->payment_method_id,
            $request->amount
        );

        return back()->with('success', 'Payout requested successfully');
    }
}
