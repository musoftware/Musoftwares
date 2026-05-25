<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserReferralRequestWithdraw;
use App\Http\Resources\WithdrawRequestResource;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminWithdrawRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = UserReferralRequestWithdraw::with(['user', 'method']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $requests = $query->orderBy('created_at', 'desc')
                          ->paginate(15)
                          ->withQueryString()
                          ->through(fn($w) => clone (new WithdrawRequestResource($w))->resolve());

        return Inertia::render('Admin/WithdrawRequests/Index', [
            'requests' => $requests,
            'filters'  => $request->only('status'),
        ]);
    }

    public function show(UserReferralRequestWithdraw $withdrawRequest)
    {
        $withdrawRequest->load(['user', 'method']);

        // Mark as reviewing if it was pending
        if ($withdrawRequest->status === 'pending') {
            $withdrawRequest->update(['status' => 'reviewing']);
        }

        return Inertia::render('Admin/WithdrawRequests/Show', [
            'withdrawRequest' => clone (new WithdrawRequestResource($withdrawRequest))->resolve(),
        ]);
    }

    public function update(Request $request, UserReferralRequestWithdraw $withdrawRequest)
    {
        $request->validate([
            'status' => 'required|in:pending,approved,declined,reviewing',
        ]);

        $withdrawRequest->changeStatus($request->status);

        return redirect()->route('admin.withdraw-requests.index')->with('success', 'Status updated successfully.');
    }
}
