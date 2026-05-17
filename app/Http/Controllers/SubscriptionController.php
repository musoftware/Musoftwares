<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\ERP\Models\ModulePlan;
use Modules\ERP\Models\UserSubscription;
use Modules\ERP\Models\SubscriptionInvoice;
use Modules\ERP\Models\Tenant;
use Modules\Core\Models\Wallet;
use Modules\Core\Models\WalletTransaction;
use App\Helpers\KashierHelper;
use App\Services\SubscriptionService;
use Carbon\Carbon;
use Inertia\Inertia;

class SubscriptionController extends Controller
{
    protected $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * Show SaaS pricing and subscription plans.
     */
    public function plans(Request $request)
    {
        $module = $request->query('module', 'erp');
        $user = Auth::user();
        
        // Fetch active plans for this module
        $plans = ModulePlan::where('module', $module)
            ->where('is_active', true)
            ->orderBy('price', 'asc')
            ->get();

        // Get current active subscription for this module if any
        $activeSub = $this->subscriptionService->getActiveSubscription($user, $module);
        
        // Get wallet details
        $wallet = $user->getWallet();
        
        return Inertia::render('Subscriptions/Plans', [
            'plans' => $plans,
            'activeSubscription' => $activeSub ? [
                'id' => $activeSub->id,
                'plan_id' => $activeSub->plan_id,
                'plan_name' => $activeSub->plan->name,
                'status' => $activeSub->status,
                'expires_at' => $activeSub->expires_at?->format('M d, Y') ?? '-',
                'auto_renew' => $activeSub->auto_renew,
            ] : null,
            'walletBalance' => (float) ($wallet->balance ?? 0),
            'currency' => $wallet->currency ?? 'USD',
            'module' => $module,
        ]);
    }

    /**
     * Subscribe using Wallet Balance (direct deduction).
     */
    public function subscribe(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:module_plans,id',
        ]);

        $user = Auth::user();
        $plan = ModulePlan::findOrFail($request->plan_id);
        $wallet = $user->getWallet();
        $price = (float) $plan->price;

        // 1. Check wallet balance
        if ($wallet->balance < $price) {
            return back()->withErrors(['error' => 'Insufficient wallet balance. Please add funds or pay via Kashier.']);
        }

        try {
            $invoice = DB::transaction(function () use ($user, $plan, $wallet, $price) {
                // 2. Deduct from wallet
                $balanceBefore = $wallet->balance;
                $balanceAfter = $balanceBefore - $price;

                $wallet->balance = $balanceAfter;
                $wallet->save();

                // 3. Record Wallet Transaction
                WalletTransaction::create([
                    'wallet_id' => $wallet->id,
                    'type' => 'debit',
                    'amount' => $price,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                    'reference_type' => 'subscription_purchase',
                    'description' => "Subscription payment for {$plan->name} ({$plan->billing})",
                ]);

                // 4. Create/Update User Subscription
                $duration = $plan->billing === 'yearly' ? 12 : 1;
                $expiresAt = Carbon::now()->addMonths($duration);

                // Cancel any previous subscriptions first
                UserSubscription::where('client_id', $user->id)
                    ->whereHas('plan', function ($q) use ($plan) {
                        $q->where('module', $plan->module);
                    })
                    ->update(['status' => 'expired']);

                $subscription = UserSubscription::create([
                    'client_id' => $user->id,
                    'plan_id' => $plan->id,
                    'status' => 'active',
                    'started_at' => Carbon::now(),
                    'expires_at' => $expiresAt,
                    'auto_renew' => true,
                ]);

                // 5. Generate Subscription Invoice
                $invoiceNum = 'INV-SUB-' . strtoupper($plan->module) . '-' . time() . '-' . $user->id;
                
                return SubscriptionInvoice::create([
                    'user_id' => $user->id,
                    'plan_id' => $plan->id,
                    'invoice_number' => $invoiceNum,
                    'amount' => $price,
                    'currency' => $wallet->currency ?? 'USD',
                    'status' => 'paid',
                    'payment_method' => 'wallet',
                    'transaction_reference' => 'wallet_deduction',
                    'paid_at' => Carbon::now(),
                ]);
            });

            // 6. ERP Redirect Logic
            if ($plan->module === 'erp') {
                $tenantExists = Tenant::where('user_id', $user->id)->exists();
                if (!$tenantExists) {
                    return redirect()->route('erp.onboarding')->with('success', 'Subscription activated! Let\'s configure your Business OS workspace.');
                }
                return redirect()->route('erp.dashboard')->with('success', 'Subscription renewed successfully!');
            }

            return redirect()->route('subscriptions.manage')->with('success', "Subscription to {$plan->name} activated successfully!");

        } catch (\Exception $e) {
            Log::error('Subscription via wallet failed: ' . $e->getMessage());
            return back()->withErrors(['error' => 'An error occurred while processing your subscription: ' . $e->getMessage()]);
        }
    }

    /**
     * Subscribe using Kashier payment gateway checkout redirect.
     */
    public function checkoutKashier(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:module_plans,id',
        ]);

        $user = Auth::user();
        $plan = ModulePlan::findOrFail($request->plan_id);
        $wallet = $user->getWallet();

        $paymentUrl = KashierHelper::buildSubscriptionPaymentUrl(
            (float) $plan->price,
            $user->id,
            $user->name,
            $user->email,
            $plan->id,
            $wallet->currency ?? 'USD'
        );

        return Inertia::location($paymentUrl);
    }

    /**
     * Kashier Webhook (Secure callback triggered by Kashier payment servers).
     */
    public function webhook(Request $request)
    {
        Log::info('Kashier Subscription Webhook received:', $request->all());

        if (KashierHelper::validatePayload()) {
            if ($request->input('data.status') === 'SUCCESS') {
                $data = $request->input('data');
                $metadata = $data['metaData'] ?? [];
                if (is_string($metadata)) {
                    $metadata = json_decode($metadata, true) ?: [];
                }

                $userId = $metadata['user_id'] ?? null;
                $planId = $metadata['plan_id'] ?? null;
                $source = $metadata['source'] ?? null;
                $trxId = $data['transactionId'] ?? null;
                $amountPaid = floatval($data['amount'] ?? 0);

                if ($userId && $planId && $source === 'subscription-purchase' && $trxId && $amountPaid > 0) {
                    $user = \App\Models\User::find($userId);
                    $plan = ModulePlan::find($planId);

                    if ($user && $plan) {
                        // Idempotency check
                        $alreadyProcessed = SubscriptionInvoice::where('transaction_reference', $trxId)->exists();

                        if (!$alreadyProcessed) {
                            DB::transaction(function () use ($user, $plan, $trxId, $amountPaid) {
                                // 1. Deactivate old subscriptions for this module
                                UserSubscription::where('client_id', $user->id)
                                    ->whereHas('plan', function ($q) use ($plan) {
                                        $q->where('module', $plan->module);
                                    })
                                    ->update(['status' => 'expired']);

                                // 2. Create new active subscription
                                $duration = $plan->billing === 'yearly' ? 12 : 1;
                                $expiresAt = Carbon::now()->addMonths($duration);

                                UserSubscription::create([
                                    'client_id' => $user->id,
                                    'plan_id' => $plan->id,
                                    'status' => 'active',
                                    'started_at' => Carbon::now(),
                                    'expires_at' => $expiresAt,
                                    'auto_renew' => true,
                                ]);

                                // 3. Create paid subscription invoice
                                $invoiceNum = 'INV-SUB-' . strtoupper($plan->module) . '-' . time() . '-' . $user->id;
                                SubscriptionInvoice::create([
                                    'user_id' => $user->id,
                                    'plan_id' => $plan->id,
                                    'invoice_number' => $invoiceNum,
                                    'amount' => $amountPaid,
                                    'currency' => $plan->currency ?? 'USD',
                                    'status' => 'paid',
                                    'payment_method' => 'kashier',
                                    'transaction_reference' => $trxId,
                                    'paid_at' => Carbon::now(),
                                ]);
                            });

                            Log::info("Kashier subscription payment processed successfully. User: $userId, Plan: {$plan->name}");
                            return response()->json(['status' => 'success', 'message' => 'Subscription activated']);
                        } else {
                            Log::warning("Duplicate Kashier subscription webhook received for Trx $trxId - skipped");
                            return response()->json(['status' => 'success', 'message' => 'Already processed']);
                        }
                    }
                }
            }
            return response()->json(['status' => 'ignored']);
        }

        return response()->json(['error' => 'Invalid webhook signature'], 400);
    }

    /**
     * Handle Kashier checkout success redirect.
     */
    public function kashierSuccess(Request $request)
    {
        $user = Auth::user();
        
        // Find if there is an active ERP subscription to redirect to onboarding
        $hasErp = $this->subscriptionService->hasActiveSubscription($user, 'erp');
        
        if ($hasErp) {
            $tenantExists = Tenant::where('user_id', $user->id)->exists();
            if (!$tenantExists) {
                return redirect()->route('erp.onboarding')->with('success', 'Payment successful! Welcome to Musoftware ERP. Let\'s setup your workspace.');
            }
            return redirect()->route('erp.dashboard')->with('success', 'Payment successful! Your ERP subscription is active.');
        }

        return redirect()->route('subscriptions.manage')->with('success', 'Payment successful! Your SaaS subscription has been activated.');
    }

    /**
     * Handle Kashier checkout failure/cancel redirect.
     */
    public function kashierFailure(Request $request)
    {
        return redirect()->route('subscriptions.plans')->with('error', 'Payment failed or was canceled. Please try again.');
    }

    /**
     * Show "My Subscriptions" management page.
     */
    public function manage(Request $request)
    {
        $user = Auth::user();

        // Fetch user subscriptions
        $subscriptions = UserSubscription::with('plan')
            ->where('client_id', $user->id)
            ->get()
            ->map(function ($sub) {
                return [
                    'id' => $sub->id,
                    'module' => strtoupper($sub->plan->module),
                    'plan_name' => $sub->plan->name,
                    'price' => (float) $sub->plan->price,
                    'billing' => $sub->plan->billing,
                    'status' => $sub->status,
                    'started_at' => $sub->started_at->format('M d, Y'),
                    'expires_at' => $sub->expires_at ? $sub->expires_at->format('M d, Y') : 'Lifetime',
                    'auto_renew' => $sub->auto_renew,
                ];
            });

        // Fetch transaction history/invoices
        $invoices = SubscriptionInvoice::with('plan')
            ->where('user_id', $user->id)
            ->latest()
            ->get()
            ->map(function ($inv) {
                return [
                    'id' => $inv->id,
                    'invoice_number' => $inv->invoice_number,
                    'plan_name' => $inv->plan->name,
                    'module' => strtoupper($inv->plan->module),
                    'amount' => (float) $inv->amount,
                    'currency' => $inv->currency,
                    'status' => $inv->status,
                    'payment_method' => ucfirst($inv->payment_method ?? '-'),
                    'paid_at' => $inv->paid_at ? $inv->paid_at->format('M d, Y') : '-',
                ];
            });

        $wallet = $user->getWallet();

        return Inertia::render('Subscriptions/Manage', [
            'subscriptions' => $subscriptions,
            'invoices' => $invoices,
            'walletBalance' => (float) ($wallet->balance ?? 0),
            'currency' => $wallet->currency ?? 'USD',
        ]);
    }

    /**
     * Cancel active subscription.
     */
    public function cancel(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:user_subscriptions,id',
        ]);

        $user = Auth::user();
        $subscription = UserSubscription::where('client_id', $user->id)
            ->where('id', $request->id)
            ->firstOrFail();

        // Set auto_renew to false so it cancels at expiration, or mark cancelled
        $subscription->auto_renew = false;
        $subscription->status = 'cancelled';
        $subscription->save();

        return back()->with('success', 'Your subscription auto-renewal has been cancelled.');
    }

    /**
     * Renew subscription using Wallet Balance.
     */
    public function renew(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:user_subscriptions,id',
        ]);

        $user = Auth::user();
        $subscription = UserSubscription::with('plan')
            ->where('client_id', $user->id)
            ->where('id', $request->id)
            ->firstOrFail();

        $plan = $subscription->plan;
        $wallet = $user->getWallet();
        $price = (float) $plan->price;

        if ($wallet->balance < $price) {
            return back()->withErrors(['error' => 'Insufficient wallet balance to renew. Please add funds.']);
        }

        try {
            DB::transaction(function () use ($user, $subscription, $plan, $wallet, $price) {
                // Deduct from wallet
                $balanceBefore = $wallet->balance;
                $balanceAfter = $balanceBefore - $price;

                $wallet->balance = $balanceAfter;
                $wallet->save();

                // Record Wallet Transaction
                WalletTransaction::create([
                    'wallet_id' => $wallet->id,
                    'type' => 'debit',
                    'amount' => $price,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                    'reference_type' => 'subscription_renewal',
                    'description' => "Subscription renewal for {$plan->name} ({$plan->billing})",
                ]);

                // Update expiration date
                $duration = $plan->billing === 'yearly' ? 12 : 1;
                
                // If it is active and not expired yet, extend from expires_at. Otherwise extend from now.
                $baseDate = ($subscription->status === 'active' && $subscription->expires_at->isFuture()) 
                    ? $subscription->expires_at 
                    : Carbon::now();

                $subscription->status = 'active';
                $subscription->expires_at = $baseDate->addMonths($duration);
                $subscription->auto_renew = true;
                $subscription->save();

                // Create paid subscription invoice
                $invoiceNum = 'INV-REN-' . strtoupper($plan->module) . '-' . time() . '-' . $user->id;
                SubscriptionInvoice::create([
                    'user_id' => $user->id,
                    'plan_id' => $plan->id,
                    'invoice_number' => $invoiceNum,
                    'amount' => $price,
                    'currency' => $wallet->currency ?? 'USD',
                    'status' => 'paid',
                    'payment_method' => 'wallet',
                    'transaction_reference' => 'wallet_renewal',
                    'paid_at' => Carbon::now(),
                ]);
            });

            return back()->with('success', 'Subscription renewed successfully!');

        } catch (\Exception $e) {
            Log::error('Subscription renewal failed: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Renewal failed: ' . $e->getMessage()]);
        }
    }
}
