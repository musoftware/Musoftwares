<?php

namespace Modules\WhatsappSender\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\WhatsappSender\Models\WhatsappBusiness;
use Modules\WhatsappSender\Models\WhatsappTransaction;

class WhatsappBusinessController extends Controller
{
    /**
     * Create a new Business Client profile.
     */
    public function storeBusiness(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'initial_balance' => ['nullable', 'numeric', 'min:0'],
        ]);

        $initialBalance = (float) ($validated['initial_balance'] ?? 0.0000);

        DB::transaction(function () use ($request, $validated, $initialBalance) {
            $business = WhatsappBusiness::create([
                'user_id' => $request->user()->id,
                'name' => $validated['name'],
                'wallet_balance' => $initialBalance,
                'currency' => 'USD',
                'per_message_fee' => 0.0010,
                'status' => 'active',
            ]);

            if ($initialBalance > 0) {
                WhatsappTransaction::create([
                    'whatsapp_business_id' => $business->id,
                    'user_id' => $request->user()->id,
                    'type' => 'credit_recharge',
                    'amount' => $initialBalance,
                    'balance_after' => $initialBalance,
                    'description' => 'Initial business wallet balance credit',
                ]);
            }
        });

        return redirect()->route('whatsapp.index')->with('success', 'Business profile created successfully.');
    }

    /**
     * Top-up business wallet balance.
     */
    public function rechargeWallet(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.10', 'max:10000'],
        ]);

        $business = WhatsappBusiness::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail();

        $amount = (float) $validated['amount'];

        DB::transaction(function () use ($request, $business, $amount) {
            $lockedBiz = WhatsappBusiness::where('id', $business->id)->lockForUpdate()->first();
            $newBalance = (float) $lockedBiz->wallet_balance + $amount;
            $lockedBiz->update(['wallet_balance' => $newBalance]);

            WhatsappTransaction::create([
                'whatsapp_business_id' => $lockedBiz->id,
                'user_id' => $request->user()->id,
                'type' => 'credit_recharge',
                'amount' => $amount,
                'balance_after' => $newBalance,
                'description' => "Wallet top-up (+{$amount} USD)",
            ]);
        });

        return redirect()->route('whatsapp.index')->with('success', "Wallet balance recharged by \${$amount} USD successfully.");
    }

    /**
     * Delete business client profile.
     */
    public function destroyBusiness(Request $request, int $id): RedirectResponse
    {
        $business = WhatsappBusiness::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail();

        $business->delete();

        return redirect()->route('whatsapp.index')->with('success', 'Business client profile deleted successfully.');
    }
}
