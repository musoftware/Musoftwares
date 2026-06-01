<?php

namespace Modules\AffiliatePos\app\Features\OrderManagement\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
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

        $payouts = $query->paginate(50)->through(fn($item) => [
            'id' => $item->id,
            'user' => [
                'name' => $item->user->name,
                'email' => $item->user->email,
            ],
            'payment_method' => [
                'name' => $item->payment_method->name,
            ],
            'amount' => $item->amount,
            'account_number' => $item->account_number,
            'account_name' => $item->account_name,
            'status' => $item->status,
            'created_at' => $item->created_at->format('Y-m-d H:i:s')
        ]);

        return Inertia::render('AffiliatePos/Admin/Payouts/Index', [
            'payouts' => $payouts,
            'filters' => $request->only(['status'])
        ]);
    }

    public function process(Request $request, PaymentRequest $paymentRequest)
    {
        $request->validate([
            'status' => 'required|in:approved,declined'
        ]);

        $this->paymentService->processPayout($paymentRequest, $request->status);
        
        return back()->with('success', __('general.payout_processed_successfully'));
    }
}
