<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserLoan\StoreLoanRepaymentRequest;
use App\Http\Requests\Admin\UserLoan\StoreUserLoanRequest;
use App\Http\Requests\Admin\UserLoan\UpdateUserLoanRequest;
use App\Models\User;
use App\Models\UserLoan;
use Illuminate\Http\RedirectResponse;

class AdminUserLoanController extends Controller
{
    public function store(StoreUserLoanRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();

        $user->loans()->create([
            'amount' => $validated['amount'],
            'paid_amount' => 0,
            'currency_id' => $validated['currency_id'],
            'date' => $validated['date'],
            'note' => $validated['note'] ?? null,
            'status' => 'active',
        ]);

        return redirect()->back()->with('success', __('admin.loan_added_successfully'));
    }

    public function update(UpdateUserLoanRequest $request, User $user, UserLoan $loan): RedirectResponse
    {
        $validated = $request->validated();

        $loan->update([
            'date' => $validated['date'],
            'note' => $validated['note'] ?? null,
        ]);

        return redirect()->back()->with('success', __('admin.loan_updated_successfully'));
    }

    public function destroy(User $user, UserLoan $loan): RedirectResponse
    {
        $loan->delete();

        return redirect()->back()->with('success', __('admin.loan_deleted_successfully'));
    }

    public function storeRepayment(StoreLoanRepaymentRequest $request, User $user, UserLoan $loan): RedirectResponse
    {
        $validated = $request->validated();
        $amount = (float) $validated['amount'];

        // Calculate if amount exceeds remaining
        $remaining = $loan->amount - $loan->paid_amount;
        if ($amount > $remaining) {
            return redirect()->back()->with('error', __('admin.repayment_exceeds_loan_amount'));
        }

        $loan->repayments()->create([
            'amount' => $amount,
            'date' => $validated['date'],
            'note' => $validated['note'] ?? null,
        ]);

        $loan->paid_amount += $amount;
        if ($loan->paid_amount >= $loan->amount) {
            $loan->status = 'paid';
        }
        $loan->save();

        return redirect()->back()->with('success', __('admin.repayment_added_successfully'));
    }
}
