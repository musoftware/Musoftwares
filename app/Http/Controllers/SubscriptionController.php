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

        $egpRate = 50; // Fallback
        if ($usdCurrency && $egpCurrencyId) {
            $egpRate = \App\Models\CurrenciesExchange::RateToday(1, $usdCurrency->id, $egpCurrencyId) ?: 50;
        }

        // Base prices are given in EGP
        $basePricesEGP = config('saas.modules', []);

        // Convert EGP to user's currency: (EGP / EGP_Rate) * User_Rate
        $convertPrice = function($egpPrice) use ($egpRate, $rate) {
            $usdPrice = $egpPrice / $egpRate;
            return round($usdPrice * $rate, 2);
        };

        $serviceItems = [
            [
                'id' => 'erp',
                'slug' => 'erp',
                'name' => 'ERP',
                'type' => 'module',
                'description' => 'Enterprise Resource Planning system for full business management.',
                'monthly_price' => $convertPrice($basePricesEGP['erp'] / 10),
                'yearly_price' => $convertPrice($basePricesEGP['erp']),
                'icon' => 'Building2'
            ],
            [
                'id' => 'crm',
                'slug' => 'crm',
                'name' => 'CRM',
                'type' => 'module',
                'description' => 'Customer Relationship Management for leads and tickets.',
                'monthly_price' => $convertPrice($basePricesEGP['crm'] / 10),
                'yearly_price' => $convertPrice($basePricesEGP['crm']),
                'icon' => 'MessageSquare'
            ],
            [
                'id' => 'sms-payment-gateway',
                'slug' => 'sms-payment-gateway',
                'name' => 'SMS Payment Gateway',
                'type' => 'module',
                'description' => 'Automated SMS marketing and gateway integration.',
                'monthly_price' => $convertPrice($basePricesEGP['sms-payment-gateway'] / 10),
                'yearly_price' => $convertPrice($basePricesEGP['sms-payment-gateway']),
                'icon' => 'Zap'
            ],
            [
                'id' => 'gold-saver',
                'slug' => 'gold-saver',
                'name' => 'Gold Saver',
                'type' => 'module',
                'description' => 'Gold savings and investment tracking system.',
                'monthly_price' => $convertPrice($basePricesEGP['gold-saver'] / 10),
                'yearly_price' => $convertPrice($basePricesEGP['gold-saver']),
                'icon' => 'Sparkles'
            ],
            [
                'id' => 'booking',
                'slug' => 'booking',
                'name' => 'Booking',
                'type' => 'module',
                'description' => 'Appointment and reservation booking engine.',
                'monthly_price' => $convertPrice($basePricesEGP['booking'] / 10),
                'yearly_price' => $convertPrice($basePricesEGP['booking']),
                'icon' => 'Check'
            ]
        ];

        // ADD-ONS INJECTION
        $addonsConfig = config('saas.addons', []);

        foreach ($addonsConfig as $id => $configItem) {
            $serviceItems[] = [
                'id' => $id,
                'slug' => $id,
                'name' => $configItem['name'],
                'type' => 'addon',
                'parent_id' => $configItem['parent'],
                'description' => $configItem['desc'],
                'monthly_price' => $convertPrice($configItem['price'] / 10),
                'yearly_price' => $convertPrice($configItem['price']),
                'icon' => $configItem['icon']
            ];
        }

        try {
            $configTools = config('tools', []);
            foreach ($configTools as $guid => $tool) {
                if (!isset($tool['is_active']) || !$tool['is_active']) {
                    continue;
                }

                $serviceItems[] = [
                    'id' => 'tool-' . $guid,
                    'slug' => $tool['slug'] ?? $guid,
                    'tool_id' => $guid,
                    'name' => $tool['title'] ?? 'Unknown Tool',
                    'type' => 'tool',
                    'description' => null, // User requested to hide description
                    'monthly_price' => $convertPrice($basePricesEGP['tool'] / 10),
                    'yearly_price' => $convertPrice($basePricesEGP['tool']),
                    'icon' => 'Wrench'
                ];
            }
        } catch (\Exception $e) {
            // Fallback if something fails
            $serviceItems[] = [
                'id' => 'tool-wa',
                'slug' => 'tool-wa',
                'name' => 'WhatsApp Sender Pro',
                'type' => 'tool',
                'description' => null,
                'monthly_price' => $convertPrice($basePricesEGP['tool'] / 10),
                'yearly_price' => $convertPrice($basePricesEGP['tool']),
                'icon' => 'Wrench'
            ];
            $serviceItems[] = [
                'id' => 'tool-extractor',
                'slug' => 'tool-extractor',
                'name' => 'Data Extractor',
                'type' => 'tool',
                'description' => null,
                'monthly_price' => $convertPrice($basePricesEGP['tool'] / 10),
                'yearly_price' => $convertPrice($basePricesEGP['tool']),
                'icon' => 'Wrench'
            ];
        }

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
            'plans' => [],
            'serviceItems' => $serviceItems,
            'activeSubscription' => $activeSub,
            'walletBalance' => (float) $user->available_balance(),
            'currency' => $currencyCode,
        ]);
    }

    private function calculateCustomPriceBackend($selectedItems, $billingCycle, $currencyId)
    {
        $usdCurrency = \App\Models\Currency::where('currency', 'USD')->first();
        $usdCurrencyId = $usdCurrency ? $usdCurrency->id : 1;
        
        $egpCurrency = \App\Models\Currency::where('currency', 'EGP')->first();
        $egpCurrencyId = $egpCurrency ? $egpCurrency->id : 1;
        
        $rate = 1.0;
        if ($usdCurrency && $currencyId && $usdCurrency->id != $currencyId) {
            $rate = \App\Models\CurrenciesExchange::RateToday(1, $usdCurrency->id, $currencyId);
        }

        $egpRate = 50;
        if ($usdCurrency && $egpCurrencyId) {
            $egpRate = \App\Models\CurrenciesExchange::RateToday(1, $usdCurrency->id, $egpCurrencyId) ?: 50;
        }

        // EGP Base prices per year
        $basePricesEGP = array_merge(
            config('saas.modules', []),
            array_map(fn($addon) => $addon['price'], config('saas.addons', []))
        );
        
        $totalUsd = 0;
        $toolsCount = 0;
        
        foreach ($selectedItems as $item) {
            if (isset($basePricesEGP[$item])) {
                $totalUsd += ($basePricesEGP[$item] / 10) / $egpRate; // Convert Monthly EGP to USD
            } elseif (str_starts_with($item, 'tool-')) {
                $toolsCount++;
            }
        }

        // Apply tool volume discount
        if ($toolsCount > 0) {
            $toolBaseMonthlyEGP = 100; // 1000 yearly / 10
            $discountPercent = min(50, ($toolsCount - 1) * 10);
            $toolsTotalEGP = ($toolBaseMonthlyEGP * $toolsCount) * (1 - ($discountPercent / 100));
            $totalUsd += ($toolsTotalEGP / $egpRate); // Convert discounted total EGP to USD
        }

        $multiplier = 1;
        if ($billingCycle === '6_months') {
            $multiplier = 6;
        } elseif ($billingCycle === '1_year') {
            $multiplier = 10; // 2 months free
        } elseif ($billingCycle === '1_month') {
            $multiplier = 1;
        }

        $totalUsd = $totalUsd * $multiplier;
        
        return $totalUsd * $rate;
    }

    private function getOrCreateCustomPlan($selectedItems, $billingCycle, $currencyId)
    {
        sort($selectedItems);
        $signature = md5(implode(',', $selectedItems) . '-' . $billingCycle . '-' . $currencyId);
        $planName = 'Custom Plan - ' . strtoupper(substr($signature, 0, 6));

        $price = $this->calculateCustomPriceBackend($selectedItems, $billingCycle, $currencyId);

        $days = 30;
        if ($billingCycle === '6_months') $days = 180;
        if ($billingCycle === '1_year') $days = 365;

        $plan = Plan::firstOrCreate(
            ['plan_name' => $planName],
            [
                'plan_price' => $price,
                'plan_duration' => $days,
                'plan_status' => true,
                'plan_currency' => $currencyId
            ]
        );
        
        $plan->plan_price = $price;
        $plan->plan_duration = $days;
        $plan->save();

        return $plan;
    }

    /**
     * Subscribe to a fixed plan using wallet balance.
     */
    public function subscribe(Request $request)
    {
        $request->validate([
            'plan_id'       => 'nullable|exists:plans,id',
            'items'         => 'nullable|array',
            'billing_cycle' => 'required|string',
        ]);

        $user = Auth::user();
        
        $usdCurrency = \App\Models\Currency::where('currency', 'USD')->first();
        $usdCurrencyId = $usdCurrency ? $usdCurrency->id : 1;
        
        $egpCurrency = \App\Models\Currency::where('currency', 'EGP')->first();
        $egpCurrencyId = $egpCurrency ? $egpCurrency->id : 1;
        
        $userCurrencyId = $user->currency_id ?: $egpCurrencyId;
        
        if ($request->has('items') && count($request->items) > 0) {
            $plan = $this->getOrCreateCustomPlan($request->items, $request->input('billing_cycle', '1_year'), $userCurrencyId);
        } else {
            if (!$request->plan_id) return back()->withErrors(['error' => 'No plan or items selected.']);
            $plan = Plan::findOrFail($request->plan_id);
        }
        
        $rate = 1.0;
        if ($usdCurrency && $userCurrencyId && $usdCurrency->id != $userCurrencyId) {
            $rate = \App\Models\CurrenciesExchange::RateToday(1, $usdCurrency->id, $userCurrencyId);
        }

        $billingCycle = $request->input('billing_cycle', '1_year');
        $multiplier = 1;
        $days = $plan->plan_duration;
        
        if ($plan->plan_name !== 'Trial' && !str_starts_with($plan->plan_name, 'Custom Plan')) {
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

                // Save capabilities
                if (isset($request->items) && is_array($request->items) && $user->tenant_id) {
                    foreach ($request->items as $item) {
                        \App\Models\TenantFeature::updateOrCreate(
                            ['tenant_id' => $user->tenant_id, 'feature_key' => $item],
                            [
                                'module' => str_starts_with($item, 'crm') ? 'crm' : (str_starts_with($item, 'erp') ? 'erp' : (str_starts_with($item, 'tool') ? 'tools' : 'booking')),
                                'plan_id' => $plan->id,
                                'expires_at' => \Carbon\Carbon::parse($user->subscription_date)
                            ]
                        );
                    }
                }

                // Cancel old platform subscriptions
                // (Removed PlatformSubscription usage)
                
                // Create new platform subscription
                // (Removed PlatformSubscription usage)
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
            'plan_id'       => 'nullable|exists:plans,id',
            'items'         => 'nullable|array',
            'billing_cycle' => 'required|string',
        ]);

        $user = Auth::user();
        
        $usdCurrency = \App\Models\Currency::where('currency', 'USD')->first();
        $usdCurrencyId = $usdCurrency ? $usdCurrency->id : 1;
        
        $egpCurrency = \App\Models\Currency::where('currency', 'EGP')->first();
        $egpCurrencyId = $egpCurrency ? $egpCurrency->id : 1;
        
        $userCurrencyId = $user->currency_id ?: $egpCurrencyId;
        $userCurrency = \App\Models\Currency::find($userCurrencyId);
        $currencyCode = $userCurrency ? $userCurrency->currency : 'USD';

        if ($request->has('items') && count($request->items) > 0) {
            $plan = $this->getOrCreateCustomPlan($request->items, $request->input('billing_cycle', '1_year'), $userCurrencyId);
        } else {
            if (!$request->plan_id) return back()->withErrors(['error' => 'No plan or items selected.']);
            $plan = Plan::findOrFail($request->plan_id);
        }
        
        $rate = 1.0;
        if ($usdCurrency && $userCurrencyId && $usdCurrency->id != $userCurrencyId) {
            $rate = \App\Models\CurrenciesExchange::RateToday(1, $usdCurrency->id, $userCurrencyId);
        }

        $billingCycle = $request->input('billing_cycle', '1_year');
        $multiplier = 1;
        $days = $plan->plan_duration; 
        
        if ($plan->plan_name !== 'Trial' && !str_starts_with($plan->plan_name, 'Custom Plan')) {
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
            $days,
            $request->items ?? []
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

                                    // Save capabilities
                                    $items = $metadata['items'] ?? [];
                                    if (is_array($items) && $user->tenant_id) {
                                        foreach ($items as $item) {
                                            \App\Models\TenantFeature::updateOrCreate(
                                                ['tenant_id' => $user->tenant_id, 'feature_key' => $item],
                                                [
                                                    'module' => str_starts_with($item, 'crm') ? 'crm' : (str_starts_with($item, 'erp') ? 'erp' : (str_starts_with($item, 'tool') ? 'tools' : 'booking')),
                                                    'plan_id' => $plan->id,
                                                    'expires_at' => \Carbon\Carbon::parse($user->subscription_date)
                                                ]
                                            );
                                        }
                                    }
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
