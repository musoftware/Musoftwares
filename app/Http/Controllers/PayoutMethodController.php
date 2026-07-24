<?php

namespace App\Http\Controllers;

use App\Models\PayoutMethod;
use App\Models\UserReferralRequestWithdraw;
use Illuminate\Http\Request;
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

        return Inertia::render('Client/Financial/PayoutMethods', [
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
        $this->validatePayoutDetails($request, (string) $type);

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

        return back()->with('success', __('general.payout_method_added_successfully'));
    }

    public function update(Request $request, PayoutMethod $payoutMethod)
    {
        if ($payoutMethod->user_id !== $request->user()->id) {
            abort(403);
        }

        $type = $request->input('type', $payoutMethod->type);
        $this->validatePayoutDetails($request, (string) $type);

        if ($request->is_default) {
            $request->user()->payoutMethods()->where('id', '!=', $payoutMethod->id)->update(['is_default' => false]);
        }

        $payoutMethod->update([
            'type' => $request->type,
            'details' => $request->details,
            'is_default' => $request->is_default ?? false,
        ]);

        return back()->with('success', __('general.payout_method_updated_successfully'));
    }

    private function validatePayoutDetails(Request $request, string $type): array
    {
        $rules = [
            'type' => 'required|string|in:bank_transfer,paypal,vodafone_cash,instapay',
            'details' => 'required|array',
            'is_default' => 'boolean',
        ];

        switch ($type) {
            case 'bank_transfer':
                $rules['details.full_name'] = 'required|string|max:255';
                $rules['details.bank_name'] = 'required|string|max:255';
                $rules['details.account_number'] = 'required|string|max:50';
                $rules['details.routing_number'] = 'nullable|string|max:50';
                break;

            case 'vodafone_cash':
                $rules['details.mobile_number'] = 'required|string|regex:/^[0-9]{10,15}$/';
                break;

            case 'paypal':
                $rules['details.paypal_email'] = 'required|email|max:255';
                break;

            case 'instapay':
                $rules['details.mobile_number'] = 'required|string|regex:/^[0-9]{10,15}$/';
                $rules['details.instapay_username'] = 'required|string|max:255';
                break;
        }

        return $request->validate($rules, [
            'details.mobile_number.regex' => 'Mobile number must be 10-15 digits.',
            'details.wallet_address.min' => 'Wallet address appears invalid.',
        ]);
    }

    public function destroy(Request $request, PayoutMethod $payoutMethod)
    {
        if ($payoutMethod->user_id !== $request->user()->id) {
            abort(403);
        }

        // Check if this method has pending withdrawals
        $hasPending = $payoutMethod->user_id === $request->user()->id
            && UserReferralRequestWithdraw::where('user_payment_method_id', $payoutMethod->id)
                ->whereIn('status', ['pending', 'processing'])
                ->exists();

        if ($hasPending) {
            return back()->withErrors(['delete' => 'Cannot delete a payout method with pending withdrawals.']);
        }

        $payoutMethod->delete();

        return back()->with('success', __('general.payout_method_removed_successfully'));
    }
}
