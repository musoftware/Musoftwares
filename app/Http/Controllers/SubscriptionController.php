<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Plan;
use Modules\ERP\Models\Tenant;
use App\Models\Transaction;
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
        
        $usdCurrency = \App\Models\Currency::where('currency', 'USD')->first();
        $usdCurrencyId = $usdCurrency ? $usdCurrency->id : 1;
        
        $egpCurrency = \App\Models\Currency::where('currency', 'EGP')->first();
        $egpCurrencyId = $egpCurrency ? $egpCurrency->id : 1;
        
        $userCurrencyId = $user->currency_id ?: $egpCurrencyId;
        $userCurrency = \App\Models\Currency::find($userCurrencyId);
        $currencyCode = $userCurrency ? $userCurrency->currency : 'USD';
        
        $rate = 1.0;
        if ($usdCurrency && $userCurrencyId && $usdCurrency->id != $userCurrencyId) {
            $rate = \App\Models\CurrenciesExchange::RateToday(1, $usdCurrency->id, $userCurrencyId);
        }

        // Ensure new plans exist in the DB
        Plan::updateOrCreate(
            ['plan_name' => 'Trial'],
            ['plan_price' => 0, 'plan_duration' => 1, 'plan_status' => true, 'plan_currency' => $usdCurrencyId]
        );
        Plan::updateOrCreate(
            ['plan_name' => 'Go'],
            ['plan_price' => 40, 'plan_duration' => 365, 'plan_status' => true, 'plan_currency' => $usdCurrencyId]
        );
        Plan::updateOrCreate(
            ['plan_name' => 'Plus'],
            ['plan_price' => 80, 'plan_duration' => 365, 'plan_status' => true, 'plan_currency' => $usdCurrencyId]
        );
        Plan::updateOrCreate(
            ['plan_name' => 'Pro'],
            ['plan_price' => 150, 'plan_duration' => 365, 'plan_status' => true, 'plan_currency' => $usdCurrencyId]
        );

        $plans = Plan::where('plan_status', true)
            ->whereIn('plan_name', ['Trial', 'Go', 'Plus', 'Pro'])
            ->orderByRaw("FIELD(plan_name, 'Trial', 'Go', 'Plus', 'Pro')")
            ->get();

        $wallet = ['id' => null, 'balance' => (float)$user->user_balance, 'currency' => $user->currency_name()];

        $hasSub = $user->hasSubscription();
        $activeSub = null;

        if ($hasSub && $user->plan) {
            $activeSub = [
                'id'            => $user->plan->id,
                'plan_id'       => $user->plan->id,
                'plan_slug'     => \Str::slug($user->plan->plan_name),
                'plan_name'     => $user->plan->plan_name,
                'status'        => 'active',
                'billing_cycle' => $user->plan->plan_duration_short(),
                'amount'        => $user->plan->current_plan_price(),
                'expires_at'    => Carbon::parse($user->subscription_date)->format('M d, Y'),
                'auto_renew'    => (bool) $user->subscription_force,
                'custom_items'  => [],
            ];
        }

        return Inertia::render('Subscriptions/Plans', [
            'plans' => $plans->map(function ($plan) use ($rate) {
                $slug = \Str::slug($plan->plan_name);
                
                // Defaults
                $features = [];
                $included_tools = [];
                
                if ($slug === 'go') {
                    $features = ['1 Tool of your choice', 'Basic support'];
                    $included_tools = [];
                } elseif ($slug === 'plus') {
                    $features = ['All automation tools', 'Priority support', 'API access'];
                    $included_tools = ['*'];
                } elseif ($slug === 'pro') {
                    $features = ['Complete business suite', '24/7 Priority support', 'API access', 'Custom integrations'];
                    $included_tools = ['*'];
                } elseif ($slug === 'trial') {
                    $features = ['Try all tools', 'All business modules', '1 day limit'];
                    $included_tools = ['*'];
                }

                $convertedPrice = $plan->current_plan_price();

                return [
                    'id'               => $plan->id,
                    'slug'             => $slug,
                    'name'             => $plan->plan_name,
                    'description'      => 'Standard Subscription Plan',
                    'monthly_price'    => $convertedPrice / 12,
                    'yearly_price'     => $convertedPrice,
                    'prices'           => [
                        '3_months' => $convertedPrice / 4,
                        '6_months' => $convertedPrice / 2,
                        '1_year'   => $convertedPrice,
                        '3_years'  => $convertedPrice * 3,
                    ],
                    'included_modules' => in_array($slug, ['pro', 'trial']) ? ['*'] : [],
                    'included_tools'   => $included_tools,
                    'features'         => $features,
                    'is_custom'        => false,
                ];
            }),
            'serviceItems' => [],
            'activeSubscription' => $activeSub,
            'walletBalance' => (float) $user->available_balance(),
            'currency' => $currencyCode,
        ]);
    }

    /**
     * Subscribe to a fixed plan using wallet balance.
     */
    public function subscribe(Request $request)
    {
        $request->validate([
            'plan_id'       => 'required|exists:plans,id',
        ]);

        $plan = Plan::findOrFail($request->plan_id);
        $user = Auth::user();
        
        $usdCurrency = \App\Models\Currency::where('currency', 'USD')->first();
        $usdCurrencyId = $usdCurrency ? $usdCurrency->id : 1;
        
        $egpCurrency = \App\Models\Currency::where('currency', 'EGP')->first();
        $egpCurrencyId = $egpCurrency ? $egpCurrency->id : 1;
        
        $userCurrencyId = $user->currency_id ?: $egpCurrencyId;
        
        $rate = 1.0;
        if ($usdCurrency && $userCurrencyId && $usdCurrency->id != $userCurrencyId) {
            $rate = \App\Models\CurrenciesExchange::RateToday(1, $usdCurrency->id, $userCurrencyId);
        }

        $billingCycle = $request->input('billing_cycle', '1_year');
        $multiplier = 1;
        $days = $plan->plan_duration; 
        
        if ($plan->plan_name !== 'Trial') {
            if ($billingCycle === '3_months') {
                $multiplier = 0.25;
                $days = 90;
            } elseif ($billingCycle === '6_months') {
                $multiplier = 0.5;
                $days = 180;
            } elseif ($billingCycle === '3_years') {
                $multiplier = 3;
                $days = 365 * 3;
            }
        }

        $current_plan = null;
        $base_plan_amount = $plan->current_plan_price() * $multiplier;
        $plan_amount = $base_plan_amount;
        $current_plan_remaining_price = 0;
        
        if ($user->plan_id != null) {
            $current_plan = Plan::find($user->plan_id);
            if ($current_plan) {
                // calc the remaining days
                $current_plan_remaining_days = (strtotime($user->subscription_date) - strtotime(date('Y-m-d'))) / (60 * 60 * 24);
                $current_plan_remaining_days = round($current_plan_remaining_days);
                if ($current_plan_remaining_days > 0) {
                    $base_remaining = ($current_plan->current_plan_price() / $current_plan->plan_duration) * $current_plan_remaining_days;
                    $current_plan_remaining_price = $base_remaining;
                }
            }
        }

        if (($user->user_balance + $current_plan_remaining_price) < $plan_amount) {
            return back()->withErrors(['error' => 'Insufficient balance.']);
        }

        try {
            DB::transaction(function () use ($user, $plan, $plan_amount, $current_plan_remaining_price, $current_plan, $days, $billingCycle, $usdCurrencyId) {
                if ($current_plan != null && $current_plan_remaining_price > 0) {
                    $user->add_balance($current_plan_remaining_price, 'Refund for remaining days of ' . $current_plan->plan_name . ' plan', 'refund', null);
                }

                if ($plan_amount > 0) {
                    $user->add_balance(-1 * $plan_amount, 'Subscribe to ' . $plan->plan_name . ' plan', 'used');
                }

                $user->subscription_plan = $plan->plan_name;
                $user->subscription_date = date('Y-m-d', strtotime('+' . $days . ' day'));
                $user->plan_id = $plan->id;
                $user->subscription_force = $plan->plan_name === 'Trial' ? 0 : 1;
                $user->save();

                // Cancel old platform subscriptions
                \App\Models\PlatformSubscription::where('user_id', $user->id)
                    ->where('status', 'active')
                    ->update(['status' => 'cancelled']);

                // Create new platform subscription
                \App\Models\PlatformSubscription::create([
                    'user_id' => $user->id,
                    'plan_id' => $plan->id,
                    'billing_cycle' => $billingCycle,
                    'amount' => $plan_amount,
                    'currency' => \App\Models\Currency::find($usdCurrencyId)?->currency ?? 'USD',
                    'status' => 'active',
                    'started_at' => now(),
                    'expires_at' => now()->addDays($days),
                    'auto_renew' => $plan->plan_name === 'Trial' ? 0 : 1,
                    'custom_items' => ['*'],
                ]);
            });

            return redirect()->route('subscriptions.manage')->with('success', "Subscribed to {$plan->plan_name} successfully!");

        } catch (\Exception $e) {
            Log::error('Platform subscription failed: ' . $e->getMessage());
            return back()->withErrors(['error' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    public function subscribeCustom(Request $request)
    {
        return back()->withErrors(['error' => 'Custom plans not supported in legacy system.']);
    }

    public function calculateCustomPrice(Request $request)
    {
        return response()->json(['total' => 0, 'breakdown' => []]);
    }

    public function checkoutKashier(Request $request)
    {
        $request->validate([
            'plan_id'       => 'required|exists:plans,id',
        ]);

        $plan = Plan::findOrFail($request->plan_id);
        $user = Auth::user();
        
        $usdCurrency = \App\Models\Currency::where('currency', 'USD')->first();
        $usdCurrencyId = $usdCurrency ? $usdCurrency->id : 1;
        
        $egpCurrency = \App\Models\Currency::where('currency', 'EGP')->first();
        $egpCurrencyId = $egpCurrency ? $egpCurrency->id : 1;
        
        $userCurrencyId = $user->currency_id ?: $egpCurrencyId;
        $userCurrency = \App\Models\Currency::find($userCurrencyId);
        $currencyCode = $userCurrency ? $userCurrency->currency : 'USD';
        
        $rate = 1.0;
        if ($usdCurrency && $userCurrencyId && $usdCurrency->id != $userCurrencyId) {
            $rate = \App\Models\CurrenciesExchange::RateToday(1, $usdCurrency->id, $userCurrencyId);
        }

        $billingCycle = $request->input('billing_cycle', '1_year');
        $multiplier = 1;
        $days = $plan->plan_duration; 
        
        if ($plan->plan_name !== 'Trial') {
            if ($billingCycle === '3_months') {
                $multiplier = 0.25;
                $days = 90;
            } elseif ($billingCycle === '6_months') {
                $multiplier = 0.5;
                $days = 180;
            } elseif ($billingCycle === '3_years') {
                $multiplier = 3;
                $days = 365 * 3;
            }
        }

        $base_plan_amount = $plan->current_plan_price() * $multiplier;
        $plan_amount = $base_plan_amount;

        $paymentUrl = \App\Helpers\KashierHelper::buildSubscriptionPaymentUrl(
            $plan_amount,
            $user->id,
            $user->name,
            $user->email,
            $plan->id,
            $currencyCode,
            $billingCycle,
            $days
        );

        return Inertia::location($paymentUrl);
    }

    public function webhook(Request $request)
    {
        \Illuminate\Support\Facades\Log::info('Subscription Kashier Webhook received:', $request->all());

        if (\App\Helpers\KashierHelper::validatePayload()) {
            if ($request->input('data.status') === 'SUCCESS') {
                $data = $request->input('data');
                $metadata = $data['metaData'] ?? [];
                if (is_string($metadata)) {
                    $metadata = json_decode($metadata, true) ?: [];
                }

                $userId = $metadata['user_id'] ?? null;
                $trxId = $data['transactionId'] ?? null;
                $amountPaid = floatval($data['amount'] ?? 0);
                $planId = $metadata['plan_id'] ?? null;
                $days = $metadata['days'] ?? 365;

                if ($userId && $trxId && $amountPaid > 0 && $planId) {
                    $user = \App\Models\User::find($userId);
                    $plan = Plan::find($planId);
                    
                    if ($user && $plan) {
                        // Idempotency check
                        $reason = "Subscription via Kashier online payment (Trx: $trxId)";
                        $alreadyProcessed = Transaction::where('user_id', $user->id)
                            ->where('reason', $reason)
                            ->exists();

                        if (!$alreadyProcessed) {
                            try {
                                DB::transaction(function () use ($user, $plan, $amountPaid, $reason, $days) {
                                    $user->add_balance($amountPaid, $reason, 'received');
                                    
                                    // Deduct balance for plan
                                    \App\Helpers\TimerHelper::instance()->addUsed($user, $amountPaid, 'Subscribe to ' . $plan->plan_name . ' plan');

                                    $user->subscription_plan = $plan->plan_name;
                                    $user->subscription_date = date('Y-m-d', strtotime('+' . $days . ' day'));
                                    $user->plan_id = $plan->id;
                                    $user->subscription_force = 1;
                                    $user->save();
                                });
                                \Illuminate\Support\Facades\Log::info("Kashier subscription processed successfully for User $userId, Plan: $plan->plan_name");
                                return response()->json(['status' => 'success', 'message' => 'Subscription processed successfully']);
                            } catch (\Exception $e) {
                                \Illuminate\Support\Facades\Log::error('Kashier subscription failed: ' . $e->getMessage());
                            }
                        } else {
                            \Illuminate\Support\Facades\Log::warning("Duplicate Kashier webhook received for Trx $trxId - skipped");
                            return response()->json(['status' => 'success', 'message' => 'Already processed']);
                        }
                    }
                }
            }
            return response()->json(['status' => 'ignored']);
        }

        return response()->json(['error' => 'Invalid webhook signature'], 400);
    }

    public function kashierSuccess(Request $request)
    {
        return redirect()->route('subscriptions.manage')->with('success', 'Payment successful!');
    }

    public function kashierFailure(Request $request)
    {
        return redirect()->route('subscriptions.plans')->with('error', 'Payment failed or was canceled.');
    }

    /**
     * Show "My Subscriptions" management page.
     */
    public function manage(Request $request)
    {
        $user = Auth::user();
        
        $subscriptions = [];
        
        if ($user->hasSubscription() && $user->plan) {
            $subscriptions[] = [
                'id'            => $user->plan->id,
                'plan_name'     => $user->plan->plan_name,
                'plan_slug'     => \Str::slug($user->plan->plan_name),
                'billing_cycle' => $user->plan->plan_duration_short(),
                'amount'        => $user->plan->current_plan_price(),
                'currency'      => $user->currency_name(),
                'status'        => 'active',
                'started_at'    => '-', // Legacy doesn't store this exactly, just expiry
                'expires_at'    => Carbon::parse($user->subscription_date)->format('M d, Y'),
                'auto_renew'    => (bool) $user->subscription_force,
                'custom_items'  => [],
                'is_custom'     => false,
            ];
        }

        $invoices = []; // SubscriptionInvoice might not exist in legacy DB

        return Inertia::render('Subscriptions/Manage', [
            'subscriptions' => $subscriptions,
            'invoices'      => $invoices,
            'walletBalance' => (float) $user->user_balance,
            'currency'      => $user->currency_name(),
        ]);
    }

    /**
     * Cancel active subscription.
     */
    public function cancel(Request $request)
    {
        $user = Auth::user();
        $user->subscription_force = 0;
        $user->save();

        return back()->with('success', 'Your subscription auto-renewal has been cancelled.');
    }

    /**
     * Renew subscription using Wallet Balance.
     */
    public function renew(Request $request)
    {
        return back()->withErrors(['error' => 'Direct renewal not supported yet, please use standard subscription process.']);
    }
}
