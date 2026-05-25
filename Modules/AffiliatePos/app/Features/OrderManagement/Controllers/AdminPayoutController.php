<?php

namespace Modules\AffiliatePos\app\Features\OrderManagement\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\AffiliatePos\Models\PaymentRequest;
use Modules\AffiliatePos\app\Features\OrderManagement\Services\PaymentService;

class AdminPayoutController extends Controller
{
    private $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function index(Request $request)
    {
        $query = PaymentRequest::with('user', 'payment_method')->latest();
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->paginate(50));
    }

    public function process(Request $request, PaymentRequest $paymentRequest)
    {
        $request->validate([
            'status' => 'required|in:approved,declined'
        ]);

        $this->paymentService->processPayout($paymentRequest, $request->status);
        
        return response()->json(['message' => 'Payout processed successfully', 'data' => $paymentRequest]);
    }
}
