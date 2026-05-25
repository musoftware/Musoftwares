<?php

namespace Modules\SmsPaymentGateway\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Modules\SmsPaymentGateway\Models\SmsPaymentGatewayTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SmsPaymentGatewayTransactionController extends Controller
{
    /**
     * List user transactions
     * GET /api/sms-payment-gateway/transactions
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = SmsPaymentGatewayTransaction::where('user_id', $user->id);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->filled('from_date')) {
            $query->whereDate('transaction_date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('transaction_date', '<=', $request->to_date);
        }

        // Filter by amount range
        if ($request->filled('min_amount')) {
            $query->where('amount', '>=', $request->min_amount);
        }
        if ($request->filled('max_amount')) {
            $query->where('amount', '<=', $request->max_amount);
        }
        
        // Filter by sender
        if ($request->filled('sender')) {
             $query->where('sender', 'like', '%' . $request->sender . '%');
        }

        // Sort by date desc
        $query->orderBy('transaction_date', 'desc');

        // Pagination
        $perPage = $request->get('per_page', 15);
        $transactions = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $transactions->items(),
            'pagination' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ]
        ]);
    }

    /**
     * Get transaction details
     * GET /api/sms-payment-gateway/transactions/{id}
     */
    public function show($id)
    {
        $user = Auth::user();

        $transaction = SmsPaymentGatewayTransaction::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $transaction
        ]);
    }
}
