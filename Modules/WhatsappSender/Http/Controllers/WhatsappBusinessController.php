<?php

namespace Modules\WhatsappSender\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
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
            'initial_balance' => ['nullable', 'numeric', 'min:0', 'max:10000'],
        ]);

        $initialBalance = (float) ($validated['initial_balance'] ?? 0.0000);
        $user = $request->user();

        if ($initialBalance > 0) {
            $usdCurrency = Currency::where('currency', 'USD')->first();
            $usdCurrencyId = $usdCurrency ? $usdCurrency->id : 1;

            $availableInUsd = (float) $user->available_balance($usdCurrencyId);

            if ($availableInUsd < $initialBalance) {
                throw ValidationException::withMessages([
                    'initial_balance' => __('general.insufficient_wallet_balance'),
                ]);
            }
        }

        DB::transaction(function () use ($user, $validated, $initialBalance) {
            if ($initialBalance > 0) {
                $usdCurrency = Currency::where('currency', 'USD')->first();
                $usdCurrencyId = $usdCurrency ? $usdCurrency->id : 1;

                $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();
                $lockedUser->add_balance(-$initialBalance, "Initial business wallet balance ({$validated['name']})", 'used', $usdCurrencyId);
            }

            $business = WhatsappBusiness::create([
                'user_id' => $user->id,
                'name' => $validated['name'],
                'wallet_balance' => $initialBalance,
                'currency' => 'USD',
                'per_message_fee' => 0.0010,
                'status' => 'active',
            ]);

            if ($initialBalance > 0) {
                WhatsappTransaction::create([
                    'whatsapp_business_id' => $business->id,
                    'user_id' => $user->id,
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

        $user = $request->user();

        $business = WhatsappBusiness::where('user_id', $user->id)
            ->where('id', $id)
            ->firstOrFail();

        $amount = (float) $validated['amount'];

        $usdCurrency = Currency::where('currency', 'USD')->first();
        $usdCurrencyId = $usdCurrency ? $usdCurrency->id : 1;

        $availableInUsd = (float) $user->available_balance($usdCurrencyId);

        if ($availableInUsd < $amount) {
            throw ValidationException::withMessages([
                'amount' => __('general.insufficient_wallet_balance'),
            ]);
        }

        DB::transaction(function () use ($user, $business, $amount, $usdCurrencyId) {
            $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();
            $lockedBiz = WhatsappBusiness::where('id', $business->id)->lockForUpdate()->first();

            // Deduct from main user balance (converted automatically by add_balance if user currency is non-USD)
            $lockedUser->add_balance(-$amount, "WhatsApp Business Wallet Top-up ({$lockedBiz->name})", 'used', $usdCurrencyId);

            // Add to business wallet balance
            $newBalance = (float) $lockedBiz->wallet_balance + $amount;
            $lockedBiz->update(['wallet_balance' => $newBalance]);

            // Create record in business transactions ledger
            WhatsappTransaction::create([
                'whatsapp_business_id' => $lockedBiz->id,
                'user_id' => $user->id,
                'type' => 'credit_recharge',
                'amount' => $amount,
                'balance_after' => $newBalance,
                'description' => "Wallet top-up (+{$amount} USD)",
            ]);
        });

        return redirect()->route('whatsapp.index')->with('success', "Wallet balance recharged by \${$amount} USD successfully.");
    }

    /**
     * Save or regenerate custom Webhook verify token for a specific Business profile.
     */
    public function updateWebhookToken(Request $request, int $id): RedirectResponse
    {
        $business = WhatsappBusiness::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail();

        $validated = $request->validate([
            'webhook_verify_token' => ['required', 'string', 'max:255'],
        ]);

        $business->update([
            'webhook_verify_token' => trim($validated['webhook_verify_token']),
        ]);

        return redirect()->route('whatsapp.index')->with('success', __('general.webhook_settings_saved_successfully') ?? 'Business Webhook verify token updated successfully.');
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

