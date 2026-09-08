<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserLoan\RechargeLoanBalanceRequest;
use App\Http\Requests\Admin\UserLoan\StoreLoanRepaymentRequest;
use App\Http\Requests\Admin\UserLoan\StoreUserLoanRequest;
use App\Http\Requests\Admin\UserLoan\UpdateUserLoanRequest;
use App\Models\User;
use App\Models\UserLoan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class AdminUserLoanController extends Controller
{
    public function store(StoreUserLoanRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();

        $user->loans()->create([
            'amount' => $validated['amount'],
            'paid_amount' => 0,
            'currency_id' => $validated['currency_id'],
            'type' => $validated['type'] ?? 'on_client',
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

    public function rechargeBalance(RechargeLoanBalanceRequest $request, User $user, UserLoan $loan): RedirectResponse
    {
        if ($loan->user_id !== $user->id) {
            abort(404);
        }

        if ($loan->type !== 'on_business') {
            return redirect()->back()->with('error', __('admin.recharge_only_allowed_for_business_loans'));
        }

        if ($loan->status === 'paid') {
            return redirect()->back()->with('error', __('admin.loan_already_paid'));
        }

        $validated = $request->validated();
        $amount = (float) $validated['amount'];

        $remaining = (float) $loan->amount - (float) $loan->paid_amount;
        if ($amount > $remaining) {
            return redirect()->back()->with('error', __('admin.repayment_exceeds_loan_amount'));
        }

        DB::transaction(function () use ($loan, $user, $amount, $validated) {
            $reason = !empty($validated['note'])
                ? $validated['note']
                : __('admin.wallet_recharge_from_loan', ['id' => $loan->id]);

            $loan->repayments()->create([
                'amount' => $amount,
                'date' => $validated['date'],
                'note' => $reason,
            ]);

            $loan->paid_amount += $amount;
            if ($loan->paid_amount >= $loan->amount) {
                $loan->status = 'paid';
            }
            $loan->save();

            $user->add_balance(
                $amount,
                $reason,
                'received',
                $loan->currency_id,
                null,
                $validated['date']
            );
        });

        return redirect()->back()->with('success', __('admin.balance_recharged_from_loan_successfully'));
    }
}
