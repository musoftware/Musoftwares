<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserLoan;
use Illuminate\Http\Request;

class AdminUserLoanController extends Controller
{
    public function store(Request $request, User $user)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'currency_id' => 'required|exists:currencies,id',
            'date' => 'required|date',
            'note' => 'nullable|string',
        ]);

        $user->loans()->create([
            'amount' => $request->amount,
            'paid_amount' => 0,
            'currency_id' => $request->currency_id,
            'date' => $request->date,
            'note' => $request->note,
            'status' => 'active',
        ]);

        return redirect()->back()->with('success', __('admin.loan_added_successfully'));
    }

    public function update(Request $request, User $user, UserLoan $loan)
    {
        $request->validate([
            'date' => 'required|date',
            'note' => 'nullable|string',
        ]);

        $loan->update([
            'date' => $request->date,
            'note' => $request->note,
        ]);

        return redirect()->back()->with('success', __('admin.loan_updated_successfully'));
    }

    public function destroy(User $user, UserLoan $loan)
    {
        $loan->delete();

        return redirect()->back()->with('success', __('admin.loan_deleted_successfully'));
    }

    public function storeRepayment(Request $request, User $user, UserLoan $loan)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'note' => 'nullable|string',
        ]);

        // Calculate if amount exceeds remaining
        $remaining = $loan->amount - $loan->paid_amount;
        if ($request->amount > $remaining) {
            return redirect()->back()->with('error', __('admin.repayment_exceeds_loan_amount'));
        }

        $loan->repayments()->create([
            'amount' => $request->amount,
            'date' => $request->date,
            'note' => $request->note,
        ]);

        $loan->paid_amount += $request->amount;
        if ($loan->paid_amount >= $loan->amount) {
            $loan->status = 'paid';
        }
        $loan->save();

        return redirect()->back()->with('success', __('admin.repayment_added_successfully'));
    }
}
