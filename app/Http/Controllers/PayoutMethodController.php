<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Core\Models\PayoutMethod;
use Inertia\Inertia;

class PayoutMethodController extends Controller
{
    public function index(Request $request)
    {
        $payoutMethods = $request->user()->payoutMethods()->latest()->get();

        return Inertia::render('Financial/PayoutMethods', [
            'payoutMethods' => $payoutMethods,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|string|in:bank_transfer,paypal,crypto_wallet',
            'details' => 'required|array',
            'is_default' => 'boolean',
        ]);

        $user = $request->user();

        if ($request->is_default) {
            $user->payoutMethods()->update(['is_default' => false]);
        }

        $user->payoutMethods()->create([
            'type' => $request->type,
            'details' => $request->details,
            'is_default' => $request->is_default ?? false,
            'status' => 'approved', // Auto approved
        ]);

        return back()->with('success', 'Payout method added successfully.');
    }

    public function update(Request $request, PayoutMethod $payoutMethod)
    {
        if ($payoutMethod->user_id !== $request->user()->id) {
            abort(403);
        }

        $request->validate([
            'type' => 'required|string|in:bank_transfer,paypal,crypto_wallet',
            'details' => 'required|array',
            'is_default' => 'boolean',
        ]);

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

        $payoutMethod->delete();

        return back()->with('success', 'Payout method removed successfully.');
    }
}
