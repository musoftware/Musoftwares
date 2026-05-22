<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\PlatformPlan;
use App\Models\PlatformSubscription;
use App\Models\PlatformServiceItem;
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
     * Show the unified pricing page with all platform plans.
     */
    public function plans(Request $request)
    {
        $user = Auth::user();

        // Fetch all active platform plans
        $plans = PlatformPlan::active()
            ->orderBy('sort_order')
            ->get();

        // Fetch service items for the custom plan builder
        $serviceItems = PlatformServiceItem::active()
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($item) => [
                'slug'          => $item->slug,
                'name'          => $item->name,
                'type'          => $item->type,
                'description'   => $item->description,
                'monthly_price' => (float) $item->monthly_price,
                'yearly_price'  => (float) $item->yearly_price,
                'icon'          => $item->icon,
            ]);

        // Get current active subscription
        $activeSub = PlatformSubscription::with('plan')
            ->forUser($user->id)
            ->active()
            ->first();

        $wallet = $user->getWallet();

        return Inertia::render('Subscriptions/Plans', [
            'plans' => $plans->map(fn ($plan) => [
                'id'               => $plan->id,
                'slug'             => $plan->slug,
                'name'             => $plan->name,
                'description'      => $plan->description,
                'monthly_price'    => (float) $plan->monthly_price,
                'yearly_price'     => (float) $plan->yearly_price,
                'included_modules' => $plan->included_modules ?? [],
                'included_tools'   => $plan->included_tools ?? [],
                'features'         => $plan->features ?? [],
                'is_custom'        => $plan->is_custom,
            ]),
            'serviceItems' => $serviceItems,
            'activeSubscription' => $activeSub ? [
                'id'            => $activeSub->id,
                'plan_id'       => $activeSub->plan_id,
                'plan_slug'     => $activeSub->plan->slug ?? null,
                'plan_name'     => $activeSub->plan->name ?? 'Custom',
                'status'        => $activeSub->status,
                'billing_cycle' => $activeSub->billing_cycle,
                'amount'        => (float) $activeSub->amount,
                'expires_at'    => $activeSub->expires_at?->format('M d, Y') ?? '-',
                'auto_renew'    => $activeSub->auto_renew,
                'custom_items'  => $activeSub->custom_items,
            ] : null,
            'walletBalance' => (float) ($wallet->balance ?? 0),
            'currency' => $wallet->currency ?? 'USD',
        ]);
    }

    /**
     * Subscribe to a fixed plan using wallet balance.
     */
    public function subscribe(Request $request)
    {
        $request->validate([
            'plan_id'       => 'required|exists:platform_plans,id',
            'billing_cycle' => 'required|in:monthly,yearly',
        ]);

        $user = Auth::user();
        $plan = PlatformPlan::findOrFail($request->plan_id);
        $wallet = $user->getWallet();
        $price = $plan->priceFor($request->billing_cycle);

        if ($plan->is_custom) {
            return back()->withErrors(['error' => 'Use the custom subscription endpoint for custom plans.']);
        }

        if ($wallet->balance < $price) {
            return back()->withErrors(['error' => 'Insufficient wallet balance. Please add funds or pay via Kashier.']);
        }

        try {
            DB::transaction(function () use ($user, $plan, $wallet, $price, $request) {
                // Deduct from wallet
                $balanceBefore = $wallet->balance;
                $balanceAfter = $balanceBefore - $price;
                $wallet->balance = $balanceAfter;
                $wallet->save();

                // Record wallet transaction
                WalletTransaction::create([
                    'wallet_id'      => $wallet->id,
                    'type'           => 'debit',
                    'amount'         => $price,
                    'balance_before' => $balanceBefore,
                    'balance_after'  => $balanceAfter,
                    'reference_type' => 'platform_subscription',
                    'description'    => "Platform subscription: {$plan->name} ({$request->billing_cycle})",
                ]);

                // Expire any previous active subscription
                PlatformSubscription::forUser($user->id)
                    ->where('status', 'active')
                    ->update(['status' => 'expired']);

                // Create new subscription
                $duration = $request->billing_cycle === 'yearly' ? 12 : 1;

                PlatformSubscription::create([
                    'user_id'           => $user->id,
                    'plan_id'           => $plan->id,
                    'billing_cycle'     => $request->billing_cycle,
                    'amount'            => $price,
                    'currency'          => $wallet->currency ?? 'USD',
                    'status'            => 'active',
                    'started_at'        => Carbon::now(),
                    'expires_at'        => Carbon::now()->addMonths($duration),
                    'auto_renew'        => true,
                    'payment_method'    => 'wallet',
                    'payment_reference' => 'wallet_deduction',
                ]);

                // Generate invoice
                $invoiceNum = 'INV-PLT-' . strtoupper($plan->slug) . '-' . time() . '-' . $user->id;
                SubscriptionInvoice::create([
                    'user_id'               => $user->id,
                    'plan_id'               => $plan->id,
                    'invoice_number'        => $invoiceNum,
                    'amount'                => $price,
                    'currency'              => $wallet->currency ?? 'USD',
                    'status'                => 'paid',
                    'payment_method'        => 'wallet',
                    'transaction_reference' => 'wallet_deduction',
                    'paid_at'               => Carbon::now(),
                ]);
            });

            // ERP redirect logic
            if ($plan->includesModule('erp')) {
                $tenantExists = Tenant::where('user_id', $user->id)->exists();
                if (!$tenantExists) {
                    return redirect()->route('erp.onboarding')->with('success', 'Subscription activated! Let\'s configure your Business OS workspace.');
                }
                return redirect()->route('erp.dashboard')->with('success', 'Subscription activated successfully!');
            }

            return redirect()->route('subscriptions.manage')->with('success', "Subscribed to {$plan->name} successfully!");

        } catch (\Exception $e) {
            Log::error('Platform subscription via wallet failed: ' . $e->getMessage());
            return back()->withErrors(['error' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    /**
     * Subscribe to a custom plan using wallet balance.
     */
    public function subscribeCustom(Request $request)
    {
        $request->validate([
            'items'         => 'required|array|min:1',
            'items.*'       => 'string|exists:platform_service_items,slug',
            'billing_cycle' => 'required|in:monthly,yearly',
        ]);

        $user = Auth::user();
        $wallet = $user->getWallet();

        // Find the custom plan
        $customPlan = PlatformPlan::where('is_custom', true)->where('is_active', true)->first();
        if (!$customPlan) {
            return back()->withErrors(['error' => 'Custom plan is not available.']);
        }

        // Calculate price
        $priceData = $this->subscriptionService->calculateCustomPrice($request->items, $request->billing_cycle);
        $price = $priceData['total'];

        if ($price <= 0) {
            return back()->withErrors(['error' => 'Invalid selection. Please choose at least one service.']);
        }

        if ($wallet->balance < $price) {
            return back()->withErrors(['error' => 'Insufficient wallet balance. Please add funds.']);
        }

        try {
            DB::transaction(function () use ($user, $customPlan, $wallet, $price, $request) {
                // Deduct from wallet
                $balanceBefore = $wallet->balance;
                $balanceAfter = $balanceBefore - $price;
                $wallet->balance = $balanceAfter;
                $wallet->save();

                WalletTransaction::create([
                    'wallet_id'      => $wallet->id,
                    'type'           => 'debit',
                    'amount'         => $price,
                    'balance_before' => $balanceBefore,
                    'balance_after'  => $balanceAfter,
                    'reference_type' => 'platform_subscription_custom',
                    'description'    => "Custom platform subscription ({$request->billing_cycle}): " . implode(', ', $request->items),
                ]);

                // Expire previous active subscription
                PlatformSubscription::forUser($user->id)
                    ->where('status', 'active')
                    ->update(['status' => 'expired']);

                $duration = $request->billing_cycle === 'yearly' ? 12 : 1;

                PlatformSubscription::create([
                    'user_id'           => $user->id,
                    'plan_id'           => $customPlan->id,
                    'billing_cycle'     => $request->billing_cycle,
                    'amount'            => $price,
                    'currency'          => $wallet->currency ?? 'USD',
                    'status'            => 'active',
                    'started_at'        => Carbon::now(),
                    'expires_at'        => Carbon::now()->addMonths($duration),
                    'auto_renew'        => true,
                    'custom_items'      => $request->items,
                    'payment_method'    => 'wallet',
                    'payment_reference' => 'wallet_deduction',
                ]);

                $invoiceNum = 'INV-PLT-CUSTOM-' . time() . '-' . $user->id;
                SubscriptionInvoice::create([
                    'user_id'               => $user->id,
                    'plan_id'               => $customPlan->id,
                    'invoice_number'        => $invoiceNum,
                    'amount'                => $price,
                    'currency'              => $wallet->currency ?? 'USD',
                    'status'                => 'paid',
                    'payment_method'        => 'wallet',
                    'transaction_reference' => 'wallet_deduction',
                    'paid_at'               => Carbon::now(),
                ]);
            });

            return redirect()->route('subscriptions.manage')->with('success', 'Custom subscription activated successfully!');

        } catch (\Exception $e) {
            Log::error('Custom platform subscription failed: ' . $e->getMessage());
            return back()->withErrors(['error' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    /**
     * Calculate custom plan price (API endpoint for live preview).
     */
    public function calculateCustomPrice(Request $request)
    {
        $request->validate([
            'items'         => 'required|array|min:1',
            'items.*'       => 'string',
            'billing_cycle' => 'required|in:monthly,yearly',
        ]);

        $result = $this->subscriptionService->calculateCustomPrice($request->items, $request->billing_cycle);

        return response()->json($result);
    }

    /**
     * Subscribe using Kashier payment gateway checkout redirect.
     */
    public function checkoutKashier(Request $request)
    {
        $request->validate([
            'plan_id'       => 'required|exists:platform_plans,id',
            'billing_cycle' => 'required|in:monthly,yearly',
        ]);

        $user = Auth::user();
        $plan = PlatformPlan::findOrFail($request->plan_id);
        $wallet = $user->getWallet();
        $price = $plan->priceFor($request->billing_cycle);

        $paymentUrl = KashierHelper::buildSubscriptionPaymentUrl(
            $price,
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
        Log::info('Kashier Platform Subscription Webhook received:', $request->all());

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
                    $plan = PlatformPlan::find($planId);

                    if ($user && $plan) {
                        $alreadyProcessed = SubscriptionInvoice::where('transaction_reference', $trxId)->exists();

                        if (!$alreadyProcessed) {
                            DB::transaction(function () use ($user, $plan, $trxId, $amountPaid) {
                                // Expire old subscriptions
                                PlatformSubscription::forUser($user->id)
                                    ->where('status', 'active')
                                    ->update(['status' => 'expired']);

                                // Create new active subscription
                                $duration = 1; // Default monthly; adjust based on plan config
                                $expiresAt = Carbon::now()->addMonths($duration);

                                PlatformSubscription::create([
                                    'user_id'           => $user->id,
                                    'plan_id'           => $plan->id,
                                    'billing_cycle'     => 'monthly',
                                    'amount'            => $amountPaid,
                                    'currency'          => 'USD',
                                    'status'            => 'active',
                                    'started_at'        => Carbon::now(),
                                    'expires_at'        => $expiresAt,
                                    'auto_renew'        => true,
                                    'payment_method'    => 'kashier',
                                    'payment_reference' => $trxId,
                                ]);

                                $invoiceNum = 'INV-PLT-' . strtoupper($plan->slug) . '-' . time() . '-' . $user->id;
                                SubscriptionInvoice::create([
                                    'user_id'               => $user->id,
                                    'plan_id'               => $plan->id,
                                    'invoice_number'        => $invoiceNum,
                                    'amount'                => $amountPaid,
                                    'currency'              => $plan->currency ?? 'USD',
                                    'status'                => 'paid',
                                    'payment_method'        => 'kashier',
                                    'transaction_reference' => $trxId,
                                    'paid_at'               => Carbon::now(),
                                ]);
                            });

                            Log::info("Kashier platform subscription processed. User: $userId, Plan: {$plan->name}");
                            return response()->json(['status' => 'success', 'message' => 'Subscription activated']);
                        } else {
                            Log::warning("Duplicate Kashier webhook for Trx $trxId — skipped");
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

        $hasErp = $this->subscriptionService->hasActiveSubscription($user, 'erp');

        if ($hasErp) {
            $tenantExists = Tenant::where('user_id', $user->id)->exists();
            if (!$tenantExists) {
                return redirect()->route('erp.onboarding')->with('success', 'Payment successful! Welcome to Musoftware ERP. Let\'s setup your workspace.');
            }
            return redirect()->route('erp.dashboard')->with('success', 'Payment successful! Your subscription is active.');
        }

        return redirect()->route('subscriptions.manage')->with('success', 'Payment successful! Your subscription has been activated.');
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

        $subscriptions = PlatformSubscription::with('plan')
            ->forUser($user->id)
            ->latest()
            ->get()
            ->map(function ($sub) {
                return [
                    'id'            => $sub->id,
                    'plan_name'     => $sub->plan->name ?? 'Custom Plan',
                    'plan_slug'     => $sub->plan->slug ?? 'custom',
                    'billing_cycle' => $sub->billing_cycle,
                    'amount'        => (float) $sub->amount,
                    'currency'      => $sub->currency,
                    'status'        => $sub->status,
                    'started_at'    => $sub->started_at?->format('M d, Y') ?? '-',
                    'expires_at'    => $sub->expires_at?->format('M d, Y') ?? 'Lifetime',
                    'auto_renew'    => $sub->auto_renew,
                    'custom_items'  => $sub->custom_items,
                    'is_custom'     => $sub->plan?->is_custom ?? false,
                ];
            });

        $invoices = SubscriptionInvoice::where('user_id', $user->id)
            ->latest()
            ->get()
            ->map(function ($inv) {
                return [
                    'id'              => $inv->id,
                    'invoice_number'  => $inv->invoice_number,
                    'amount'          => (float) $inv->amount,
                    'currency'        => $inv->currency,
                    'status'          => $inv->status,
                    'payment_method'  => ucfirst($inv->payment_method ?? '-'),
                    'paid_at'         => $inv->paid_at ? $inv->paid_at->format('M d, Y') : '-',
                ];
            });

        $wallet = $user->getWallet();

        return Inertia::render('Subscriptions/Manage', [
            'subscriptions' => $subscriptions,
            'invoices'      => $invoices,
            'walletBalance' => (float) ($wallet->balance ?? 0),
            'currency'      => $wallet->currency ?? 'USD',
        ]);
    }

    /**
     * Cancel active subscription.
     */
    public function cancel(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:platform_subscriptions,id',
        ]);

        $user = Auth::user();
        $subscription = PlatformSubscription::forUser($user->id)
            ->findOrFail($request->id);

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
            'id' => 'required|exists:platform_subscriptions,id',
        ]);

        $user = Auth::user();
        $subscription = PlatformSubscription::with('plan')
            ->forUser($user->id)
            ->findOrFail($request->id);

        $plan = $subscription->plan;
        $wallet = $user->getWallet();
        $price = $plan ? $plan->priceFor($subscription->billing_cycle) : (float) $subscription->amount;

        if ($wallet->balance < $price) {
            return back()->withErrors(['error' => 'Insufficient wallet balance to renew. Please add funds.']);
        }

        try {
            DB::transaction(function () use ($user, $subscription, $plan, $wallet, $price) {
                $balanceBefore = $wallet->balance;
                $balanceAfter = $balanceBefore - $price;
                $wallet->balance = $balanceAfter;
                $wallet->save();

                WalletTransaction::create([
                    'wallet_id'      => $wallet->id,
                    'type'           => 'debit',
                    'amount'         => $price,
                    'balance_before' => $balanceBefore,
                    'balance_after'  => $balanceAfter,
                    'reference_type' => 'platform_subscription_renewal',
                    'description'    => "Subscription renewal: " . ($plan->name ?? 'Custom') . " ({$subscription->billing_cycle})",
                ]);

                $duration = $subscription->billing_cycle === 'yearly' ? 12 : 1;
                $baseDate = ($subscription->status === 'active' && $subscription->expires_at?->isFuture())
                    ? $subscription->expires_at
                    : Carbon::now();

                $subscription->status = 'active';
                $subscription->expires_at = $baseDate->addMonths($duration);
                $subscription->auto_renew = true;
                $subscription->save();

                $invoiceNum = 'INV-REN-' . strtoupper($plan->slug ?? 'CUSTOM') . '-' . time() . '-' . $user->id;
                SubscriptionInvoice::create([
                    'user_id'               => $user->id,
                    'plan_id'               => $plan?->id,
                    'invoice_number'        => $invoiceNum,
                    'amount'                => $price,
                    'currency'              => $wallet->currency ?? 'USD',
                    'status'                => 'paid',
                    'payment_method'        => 'wallet',
                    'transaction_reference' => 'wallet_renewal',
                    'paid_at'               => Carbon::now(),
                ]);
            });

            return back()->with('success', 'Subscription renewed successfully!');

        } catch (\Exception $e) {
            Log::error('Subscription renewal failed: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Renewal failed: ' . $e->getMessage()]);
        }
    }
}
