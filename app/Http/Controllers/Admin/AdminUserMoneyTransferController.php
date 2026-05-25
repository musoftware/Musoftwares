<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserMoneyTransfer;
use App\Services\UserMoneyTransferService;
use App\Http\Resources\UserMoneyTransferResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Exception;

class AdminUserMoneyTransferController extends Controller
{
    public function __construct(
        protected UserMoneyTransferService $transferService
    ) {}

    public function index(Request $request)
    {
        $query = UserMoneyTransfer::with(['sender', 'receiver', 'currencyModel']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('user_search')) {
            $search = $request->user_search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('sender', function ($sq) use ($search) {
                    $sq->where('name', 'like', "%{$search}%")
                       ->orWhere('email', 'like', "%{$search}%");
                })->orWhereHas('receiver', function ($rq) use ($search) {
                    $rq->where('name', 'like', "%{$search}%")
                       ->orWhere('email', 'like', "%{$search}%");
                });
            });
        }

        $transfers = $query->orderBy('created_at', 'desc')
                           ->paginate(20)
                           ->withQueryString()
                           ->through(fn($t) => clone (new UserMoneyTransferResource($t))->resolve());

        $statuses = UserMoneyTransfer::select('status')->distinct()->pluck('status');

        return Inertia::render('Admin/MoneyTransfers/Index', [
            'transfers' => $transfers,
            'statuses'  => $statuses,
            'filters'   => $request->only(['status', 'date_from', 'date_to', 'user_search']),
        ]);
    }

    public function show(UserMoneyTransfer $transfer)
    {
        $transfer->load([
            'sender', 'receiver', 'currencyModel', 'feeCurrencyModel',
            'convertedCurrencyModel', 'senderMainTransaction',
            'senderFeeTransaction', 'receiverMainTransaction'
        ]);

        return Inertia::render('Admin/MoneyTransfers/Show', [
            'transfer' => clone (new UserMoneyTransferResource($transfer))->resolve(),
        ]);
    }

    public function approve(UserMoneyTransfer $transfer)
    {
        if (!$transfer->canBeProcessed()) {
            return response()->json(['success' => false, 'message' => 'Transfer cannot be processed in current status'], 422);
        }

        try {
            $this->transferService->processTransfer($transfer);
            return response()->json(['success' => true, 'message' => 'Transfer approved and processed successfully']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function reject(Request $request, UserMoneyTransfer $transfer)
    {
        $request->validate(['rejection_reason' => 'required|string|max:500']);

        if (!$transfer->canBeCancelled()) {
            return response()->json(['success' => false, 'message' => 'Transfer cannot be rejected in current status'], 422);
        }

        try {
            $transfer->update([
                'status' => UserMoneyTransfer::STATUS_REJECTED,
                'admin_notes' => $request->rejection_reason,
            ]);
            return response()->json(['success' => true, 'message' => 'Transfer rejected successfully']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function cancel(Request $request, UserMoneyTransfer $transfer)
    {
        $request->validate(['cancellation_reason' => 'required|string|max:500']);

        if (!$transfer->canBeCancelled()) {
            return response()->json(['success' => false, 'message' => 'Transfer cannot be cancelled in current status'], 422);
        }

        try {
            $this->transferService->cancelTransfer($transfer, $request->cancellation_reason);
            return response()->json(['success' => true, 'message' => 'Transfer cancelled successfully']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function statistics(Request $request)
    {
        $period = $request->get('period', 'month');
        $stats = $this->transferService->getTransferStatistics($period);

        return Inertia::render('Admin/MoneyTransfers/Statistics', [
            'stats'          => $stats,
            'period'         => $period,
            'totalPending'   => UserMoneyTransfer::pending()->count(),
            'totalCompleted' => UserMoneyTransfer::completed()->count(),
            'totalFailed'    => UserMoneyTransfer::failed()->count(),
            'totalCancelled' => UserMoneyTransfer::cancelled()->count(),
        ]);
    }
}
