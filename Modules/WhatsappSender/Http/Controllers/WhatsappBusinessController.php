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
        $user = $request->user();
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'client_name' => ['nullable', 'string', 'max:255'],
            'client_email' => ['nullable', 'email', 'max:255'],
            'client_mobile' => ['nullable', 'string', 'max:255'],
            'client_whatsapp' => ['nullable', 'string', 'max:255'],
            'initial_balance' => ['nullable', 'numeric', 'min:0', 'max:10000'],
            'facebook_client_id' => ['nullable', 'string', 'max:255'],
            'facebook_client_secret' => ['nullable', 'string', 'max:255'],
        ];

        if ($user->isAdmin()) {
            $rules['per_message_fee'] = ['nullable', 'numeric', 'min:0', 'max:10'];
            $rules['bot_reply_fee'] = ['nullable', 'numeric', 'min:0', 'max:10'];
        }

        $validated = $request->validate($rules);

        $initialBalance = (float) ($validated['initial_balance'] ?? 0.0000);

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
                'client_name' => $validated['client_name'] ?? null,
                'client_email' => $validated['client_email'] ?? null,
                'client_mobile' => $validated['client_mobile'] ?? null,
                'client_whatsapp' => $validated['client_whatsapp'] ?? null,
                'wallet_balance' => $initialBalance,
                'currency' => 'USD',
                'per_message_fee' => $user->isAdmin() ? (float) ($validated['per_message_fee'] ?? 0.0010) : 0.0010,
                'bot_reply_fee' => $user->isAdmin() ? (float) ($validated['bot_reply_fee'] ?? 0.0005) : 0.0005,
                'status' => 'active',
                'webhook_verify_token' => 'biz_wt_' . \Illuminate\Support\Str::random(24),
                'facebook_client_id' => $validated['facebook_client_id'] ?? null,
                'facebook_client_secret' => $validated['facebook_client_secret'] ?? null,
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

        return redirect()->back()->with('success', 'Business profile created successfully.');
    }

    /**
     * Update an existing Business Client profile and/or pricing fees.
     */
    public function updateBusiness(Request $request, int $id): RedirectResponse
    {
        $user = $request->user();
        $businessQuery = WhatsappBusiness::where('id', $id);
        if (!$user->isAdmin()) {
            $businessQuery->where('user_id', $user->id);
        }
        $business = $businessQuery->firstOrFail();

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'client_name' => ['nullable', 'string', 'max:255'],
            'client_email' => ['nullable', 'email', 'max:255'],
            'client_mobile' => ['nullable', 'string', 'max:255'],
            'client_whatsapp' => ['nullable', 'string', 'max:255'],
            'facebook_client_id' => ['nullable', 'string', 'max:255'],
            'facebook_client_secret' => ['nullable', 'string', 'max:255'],
        ];

        if ($user->isAdmin()) {
            $rules['per_message_fee'] = ['required', 'numeric', 'min:0', 'max:10'];
            $rules['bot_reply_fee'] = ['required', 'numeric', 'min:0', 'max:10'];
        }

        $validated = $request->validate($rules);

        $updateData = [
            'name' => $validated['name'],
            'client_name' => $validated['client_name'] ?? null,
            'client_email' => $validated['client_email'] ?? null,
            'client_mobile' => $validated['client_mobile'] ?? null,
            'client_whatsapp' => $validated['client_whatsapp'] ?? null,
            'facebook_client_id' => $validated['facebook_client_id'] ?? null,
            'facebook_client_secret' => $validated['facebook_client_secret'] ?? null,
        ];

        if ($user->isAdmin()) {
            $updateData['per_message_fee'] = (float) $validated['per_message_fee'];
            $updateData['bot_reply_fee'] = (float) $validated['bot_reply_fee'];
        }

        $business->update($updateData);

        return redirect()->back()->with('success', 'Business profile updated successfully.');
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

        $businessQuery = WhatsappBusiness::where('id', $id);
        if (!$user->isAdmin()) {
            $businessQuery->where('user_id', $user->id);
        }
        $business = $businessQuery->firstOrFail();

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
            $lockedUser->add_balance(-$amount, "Business wallet recharge ({$business->name})", 'used', $usdCurrencyId);

            $lockedBusiness = WhatsappBusiness::where('id', $business->id)->lockForUpdate()->first();
            $newBalance = (float) $lockedBusiness->wallet_balance + $amount;
            $lockedBusiness->update(['wallet_balance' => $newBalance]);

            // Create record in business transactions ledger
            WhatsappTransaction::create([
                'whatsapp_business_id' => $lockedBusiness->id,
                'user_id' => $user->id,
                'type' => 'credit_recharge',
                'amount' => $amount,
                'balance_after' => $newBalance,
                'description' => "Wallet top-up (+{$amount} USD)",
            ]);
        });

        return redirect()->back()->with('success', "Wallet balance recharged by \${$amount} USD successfully.");
    }

    /**
     * Save or regenerate custom Webhook verify token for a specific Business profile.
     */
    public function updateWebhookToken(Request $request, int $id): RedirectResponse
    {
        $user = $request->user();
        $businessQuery = WhatsappBusiness::where('id', $id);
        if (!$user->isAdmin()) {
            $businessQuery->where('user_id', $user->id);
        }
        $business = $businessQuery->firstOrFail();

        $validated = $request->validate([
            'webhook_verify_token' => ['required', 'string', 'max:255'],
        ]);

        $business->update([
            'webhook_verify_token' => trim($validated['webhook_verify_token']),
        ]);

        return redirect()->back()->with('success', __('general.webhook_settings_saved_successfully') ?? 'Business Webhook verify token updated successfully.');
    }

    /**
     * Delete business client profile.
     */
    public function destroyBusiness(Request $request, int $id): RedirectResponse
    {
        $user = $request->user();
        $businessQuery = WhatsappBusiness::where('id', $id);
        if (!$user->isAdmin()) {
            $businessQuery->where('user_id', $user->id);
        }
        $business = $businessQuery->firstOrFail();

        $business->delete();

        return redirect()->route('whatsapp.index')->with('success', 'Business client profile deleted successfully.');
    }

    /**
     * Toggle Stripe-like Test Mode (Sandbox) for a business workspace.
     */
    public function toggleTestMode(Request $request, int $id): RedirectResponse
    {
        $user = $request->user();
        $businessQuery = WhatsappBusiness::where('id', $id);
        if (!$user->isAdmin()) {
            $businessQuery->where('user_id', $user->id);
        }
        $business = $businessQuery->firstOrFail();

        $newTestMode = !$business->is_test_mode;

        $updateData = [
            'is_test_mode' => $newTestMode,
        ];

        if ($newTestMode) {
            $updateData['test_phone_number_id'] = $business->test_phone_number_id ?? config('services.facebook.test_phone_number_id', '114811102562039');
            $updateData['test_waba_id'] = $business->test_waba_id ?? config('services.facebook.test_waba_id', '109283748291029');
            $updateData['test_access_token'] = $business->test_access_token ?? config('services.facebook.test_access_token', 'EAAG_META_TEST_SANDBOX_TOKEN_DEMO');

            // Auto-create Meta Sandbox Test Account if it doesn't exist for this business
            $hasTestAccount = \Modules\WhatsappSender\Models\WhatsappAccount::where('whatsapp_business_id', $business->id)
                ->where('phone_number_id', $updateData['test_phone_number_id'])
                ->exists();

            if (!$hasTestAccount) {
                \Modules\WhatsappSender\Models\WhatsappAccount::create([
                    'user_id' => $user->id,
                    'whatsapp_business_id' => $business->id,
                    'name' => 'Meta Sandbox Test Number (+1 555-0199)',
                    'phone_number_id' => $updateData['test_phone_number_id'],
                    'waba_id' => $updateData['test_waba_id'],
                    'access_token' => $updateData['test_access_token'],
                    'status' => 'active',
                    'metadata' => [
                        'is_sandbox' => true,
                        'description' => 'Meta Official WhatsApp Test Phone Number for App Reviewers & Sandbox Testing',
                    ],
                ]);
            }
        }

        $business->update($updateData);

        $statusMsg = $newTestMode
            ? 'Test Mode (Sandbox) enabled successfully! Meta Sandbox Test Account auto-loaded.'
            : 'Live Mode enabled. Real WhatsApp Business Account active.';

        return redirect()->back()->with('success', $statusMsg);
    }
}
