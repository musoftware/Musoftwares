<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PayoutMethod;
use Inertia\Inertia;

/**
 * Payout method management with type-specific validation.
 * Recovered from old project: Client/PayoutController::store_payment_method()
 * Modernized: JSON details field, expanded payment type support.
 */
class PayoutMethodController extends Controller
{
    public function index(Request $request)
    {
        $payoutMethods = $request->user()->payoutMethods()->latest()->get();

        return Inertia::render('Financial/PayoutMethods', [
            'payoutMethods' => $payoutMethods,
        ]);
    }

    /**
     * Store a new payout method with type-specific validation.
     * Recovered from old project: PayoutController::store_payment_method()
     * 
     * Old project validated per type: bank needs account_number, wallet needs mobile, etc.
     * This version uses a JSON `details` field but applies the same strict validation rules.
     */
    public function store(Request $request)
    {
        $type = $request->input('type');

        // Base validation rules
        $rules = [
            'type' => 'required|string|in:bank_transfer,paypal,mobile_wallet,instapay,crypto_wallet',
            'is_default' => 'boolean',
        ];

        // Type-specific validation rules
        // Recovered from old project: PayoutController::store_payment_method() conditional rules
        switch ($type) {
            case 'bank_transfer':
                $rules['details.full_name'] = 'required|string|max:255';
                $rules['details.bank_name'] = 'required|string|max:255';
                $rules['details.account_number'] = 'required|string|max:50';
                $rules['details.account_name'] = 'required|string|max:255';
                $rules['details.iban'] = 'nullable|string|max:34';
                $rules['details.swift_code'] = 'nullable|string|max:11';
                $rules['details.bank_country'] = 'nullable|string|max:2';
                $rules['details.currency'] = 'required|string|in:USD,EUR,GBP,EGP,TRY,CAD,AUD';
                break;

            case 'mobile_wallet':
                // Recovered from old project: wallet type requires 11-digit mobile number
                $rules['details.full_name'] = 'required|string|max:255';
                $rules['details.mobile_number'] = 'required|string|regex:/^[0-9]{10,15}$/';
                $rules['details.provider'] = 'nullable|string|max:50';
                $rules['details.currency'] = 'required|string|in:EGP,USD';
                break;

            case 'paypal':
                // Recovered from old project: paypal type requires valid email
                $rules['details.full_name'] = 'required|string|max:255';
                $rules['details.paypal_email'] = 'required|email|max:255';
                $rules['details.currency'] = 'required|string|in:USD,EUR,GBP';
                break;

            case 'instapay':
                // Recovered from old project: instapay type requires mobile + username
                $rules['details.full_name'] = 'required|string|max:255';
                $rules['details.mobile_number'] = 'required|string|regex:/^[0-9]{10,15}$/';
                $rules['details.instapay_username'] = 'required|string|max:255';
                $rules['details.currency'] = 'required|string|in:EGP';
                break;

            case 'crypto_wallet':
                $rules['details.wallet_address'] = 'required|string|min:20|max:100';
                $rules['details.network'] = 'required|string|in:BTC,ETH,USDT_TRC20,USDT_ERC20';
                $rules['details.currency'] = 'required|string|in:BTC,ETH,USDT';
                break;
        }

        $request->validate($rules, [
            'details.mobile_number.regex' => 'Mobile number must be 10-15 digits.',
            'details.wallet_address.min' => 'Wallet address appears invalid.',
        ]);

        $user = $request->user();

        // Unset all other defaults if this is being set as default
        if ($request->is_default) {
            $user->payoutMethods()->update(['is_default' => false]);
        }

        $user->payoutMethods()->create([
            'type' => $request->type,
            'details' => $request->details,
            'is_default' => $request->is_default ?? false,
            'status' => 'approved', // Auto approved for now; can be changed to 'pending' for admin review
        ]);

        return back()->with('success', 'Payout method added successfully.');
    }

    public function update(Request $request, PayoutMethod $payoutMethod)
    {
        if ($payoutMethod->user_id !== $request->user()->id) {
            abort(403);
        }

        $type = $request->input('type', $payoutMethod->type);

        // Apply same type-specific validation as store()
        $rules = [
            'type' => 'required|string|in:bank_transfer,paypal,mobile_wallet,instapay,crypto_wallet',
            'details' => 'required|array',
            'is_default' => 'boolean',
        ];

        switch ($type) {
            case 'bank_transfer':
                $rules['details.full_name'] = 'required|string|max:255';
                $rules['details.bank_name'] = 'required|string|max:255';
                $rules['details.account_number'] = 'required|string|max:50';
                $rules['details.account_name'] = 'required|string|max:255';
                $rules['details.currency'] = 'required|string|in:USD,EUR,GBP,EGP,TRY,CAD,AUD';
                break;
            case 'mobile_wallet':
                $rules['details.full_name'] = 'required|string|max:255';
                $rules['details.mobile_number'] = 'required|string|regex:/^[0-9]{10,15}$/';
                $rules['details.currency'] = 'required|string|in:EGP,USD';
                break;
            case 'paypal':
                $rules['details.full_name'] = 'required|string|max:255';
                $rules['details.paypal_email'] = 'required|email|max:255';
                $rules['details.currency'] = 'required|string|in:USD,EUR,GBP';
                break;
            case 'instapay':
                $rules['details.full_name'] = 'required|string|max:255';
                $rules['details.mobile_number'] = 'required|string|regex:/^[0-9]{10,15}$/';
                $rules['details.instapay_username'] = 'required|string|max:255';
                $rules['details.currency'] = 'required|string|in:EGP';
                break;
            case 'crypto_wallet':
                $rules['details.wallet_address'] = 'required|string|min:20|max:100';
                $rules['details.network'] = 'required|string|in:BTC,ETH,USDT_TRC20,USDT_ERC20';
                $rules['details.currency'] = 'required|string|in:BTC,ETH,USDT';
                break;
        }

        $request->validate($rules);

        if ($request->is_default) {
            $request->user()->payoutMethods()->where('id', '!=', $payoutMethod->id)->update(['is_default' => false]);
        }

        $payoutMethod->update([
            'type' => $request->type,
            'details' => $request->details,
            'is_default' => $request->is_default ?? false,
        ]);

        return back()->with('success', 'Payout method updated successfully.');
    }

    public function destroy(Request $request, PayoutMethod $payoutMethod)
    {
        if ($payoutMethod->user_id !== $request->user()->id) {
            abort(403);
        }

        // Check if this method has pending withdrawals
        $hasPending = $payoutMethod->user_id === $request->user()->id
            && \App\Models\UserReferralRequestWithdraw::where('user_payment_method_id', $payoutMethod->id)
                ->whereIn('status', ['pending', 'processing'])
                ->exists();

        if ($hasPending) {
            return back()->withErrors(['delete' => 'Cannot delete a payout method with pending withdrawals.']);
        }

        $payoutMethod->delete();

        return back()->with('success', 'Payout method removed successfully.');
    }
}
