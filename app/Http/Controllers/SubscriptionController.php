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
    use \App\Traits\ConvertsCurrency;

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
        $convertPrice = function($egpPrice) use ($egpRate, $rate, $currencyCode) {
            if ($currencyCode === 'EGP') {
                return round($egpPrice);
            }
            $usdPrice = $egpPrice / $egpRate;
            $converted = $usdPrice * $rate;
            return psychological_price($converted);
        };

        $pricingService = new \App\Services\PricingService();
        $serviceItems = $pricingService->getServiceItems($convertPrice);

        $wallet = ['id' => null, 'balance' => (float)$user->user_balance, 'currency' => $user->currency_name()];

        $ownedFeatures = [];
        $userSubs = \App\Models\UserSubscription::where('user_id', $user->id)
            ->where('status', 'active')
            ->get();

        foreach ($userSubs as $sub) {
            $ownedFeatures[] = [
                'id' => $sub->object,
                'status' => \Carbon\Carbon::parse($sub->expires_at)->isFuture() ? 'active' : 'expired',
                'expires_at' => \Carbon\Carbon::parse($sub->expires_at)->format('M d, Y')
            ];
        }

        $hasSub = count($ownedFeatures) > 0;
        $activeSub = null;

        if ($hasSub) {
            // Find the closest expiring subscription to use as the primary display date
            $closestExpiry = $userSubs->min('expires_at');
            
            $activeSub = [
                'id'            => $user->id,
                'status'        => 'active',
                'expires_at'    => $closestExpiry ? Carbon::parse($closestExpiry)->format('M d, Y') : '-',
                'auto_renew'    => false, // Handled per-module now
                'owned_features' => $ownedFeatures,
            ];
        }

        $proratedRefund = 0;

        return Inertia::render('Client/Subscriptions/Plans', [
            'plans' => [],
            'serviceItems' => $serviceItems,
            'activeSubscription' => $activeSub,
            'walletBalance' => (float) $user->available_balance(),
            'currency' => $currencyCode,
            'proratedRefund' => (float) $proratedRefund,
        ]);
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
            'is_new_system' => 'nullable|boolean',
        ]);

        $user = Auth::user();
        $this->subscriptionService->validateAddonParents($request->items, $user);
        
        $usdCurrency = \App\Models\Currency::where('currency', 'USD')->first();
        $usdCurrencyId = $usdCurrency ? $usdCurrency->id : 1;
        
        $egpCurrency = \App\Models\Currency::where('currency', 'EGP')->first();
        $egpCurrencyId = $egpCurrency ? $egpCurrency->id : 1;
        
        $userCurrencyId = $user->currency_id ?: $egpCurrencyId;
        $userCurrency = \App\Models\Currency::find($userCurrencyId);
        $currencyCode = $userCurrency ? $userCurrency->currency : 'USD';
        
        $billingCycle = $request->input('billing_cycle', '1_year');
        $multiplier = 1;
        $days = 30;
        
        if ($billingCycle === '3_months') {
            $multiplier = 0.25;
            $days = 90;
        } elseif ($billingCycle === '6_months') {
            $multiplier = 0.5;
            $days = 180;
        } elseif ($billingCycle === '1_year') {
            $multiplier = 10; // 2 months free
            $days = 365;
        }

        if ($request->has('items') && count($request->items) > 0) {
            $base_plan_amount = $this->subscriptionService->calculateCustomPriceBackend($request->items, $request->input('billing_cycle', '1_year'), $userCurrencyId);
        } else {
            return back()->withErrors(['error' => 'No modules selected.']);
        }

        $current_plan = null;
        $plan_amount = $currencyCode === 'EGP' ? round($base_plan_amount) : psychological_price($base_plan_amount);
        $current_plan_remaining_price = 0; // We no longer offer prorated refunds on module additions. Each module lives independently.
        $isNewSystem = $request->input('is_new_system', true);
        

        if (($user->user_balance + $current_plan_remaining_price) < $plan_amount) {
            return back()->withErrors(['error' => 'Insufficient balance.']);
        }

        try {
            DB::transaction(function () use ($user, $plan_amount, $days, $billingCycle, $usdCurrencyId, $isNewSystem, $request) {
                $userTenant = \Modules\ERP\Models\Tenant::where('user_id', $user->id)->first();
                if ($isNewSystem && !$userTenant) {
                    $tenantName = explode(' ', $user->name)[0] . ' Workspace ' . substr(uniqid(), -4);
                    $tenant = \Modules\ERP\Models\Tenant::create([
                        'user_id' => $user->id,
                        'name' => $tenantName,
                        'status' => 'active',
                        'base_currency_id' => $user->currency_id ?: $usdCurrencyId,
                    ]);
                    $userTenant = $tenant;
                }

                if ($plan_amount > 0) {
                    $user->add_balance(-1 * $plan_amount, 'Subscribe to modules', 'used');
                }

                // Create subscriptions for each purchased item
                if (isset($request->items) && is_array($request->items)) {
                    foreach ($request->items as $item) {
                        $expiry = \Carbon\Carbon::now()->addDays((int) $days);
                        
                        // Check if they already own it, extend expiry
                        $existing = \App\Models\UserSubscription::where('user_id', $user->id)->where('object', $item)->first();
                        if ($existing && $existing->status === 'active' && \Carbon\Carbon::parse($existing->expires_at)->isFuture()) {
                            $expiry = \Carbon\Carbon::parse($existing->expires_at)->addDays((int) $days);
                        }

                        \App\Models\UserSubscription::updateOrCreate(
                            ['user_id' => $user->id, 'object' => $item],
                            [
                                'status' => 'active',
                                'started_at' => now(),
                                'expires_at' => $expiry,
                                'auto_renew' => true
                            ]
                        );

                        // Also update tenant_features for system permissions if tenant exists
                        if ($userTenant) {
                            \App\Models\TenantFeature::updateOrCreate(
                                ['tenant_id' => $userTenant->id, 'feature_key' => $item],
                                [
                                    'module' => str_starts_with($item, 'crm') ? 'crm' : (str_starts_with($item, 'erp') ? 'erp' : (str_starts_with($item, 'tool') ? 'tools' : 'booking')),
                                    'expires_at' => $expiry
                                ]
                            );
                        }
                    }
                }
            });

            return redirect()->route('subscriptions.manage')->with('success', __('general.subscribed_to_modules_successfully'));

        } catch (\Exception $e) {
            Log::error('Platform subscription failed: ' . $e->getMessage());
            return back()->withErrors(['error' => 'An error occurred: ' . $e->getMessage()]);
        }
    }


    public function subscribeCustom(Request $request)
    {
        return back()->withErrors(['error' => 'Custom plans not supported in legacy system.']);
    }

    public function calculateCustomPrice(Request $request, \App\Services\IpGeolocationService $geoService)
    {
        $request->validate([
            'items' => 'nullable|array',
            'billing_cycle' => 'required|string',
        ]);

        $user = Auth::user();
        
        $usdCurrency = \App\Models\Currency::where('currency', 'USD')->first();
        $usdCurrencyId = $usdCurrency ? $usdCurrency->id : 1;
        
        $currencyId = null;
        
        if ($user && $user->currency_id) {
            $currencyId = $user->currency_id;
        } else {
            $ipCurrencyCode = $geoService->getCurrencyCodeForIp($request->ip());
            if ($ipCurrencyCode) {
                $ipCurrency = \App\Models\Currency::where('currency', $ipCurrencyCode)->first();
                if ($ipCurrency) {
                    $currencyId = $ipCurrency->id;
                }
            }
            if (!$currencyId) {
                $currencyId = $usdCurrencyId;
            }
        }
        
        $userCurrency = \App\Models\Currency::find($currencyId);
        $currencyCode = $userCurrency ? $userCurrency->currency : 'USD';

        $items = $request->input('items', []);
        $billingCycle = $request->input('billing_cycle', '1_month');

        if (empty($items)) {
            return response()->json([
                'toolsDiscount' => 0,
                'annualDiscount' => 0,
                'total' => 0
            ]);
        }

        $breakdown = $this->subscriptionService->calculateCustomPriceBackend($items, $billingCycle, $currencyId, true);

        if ($currencyCode !== 'EGP') {
            $breakdown['total'] = psychological_price($breakdown['total']);
        } else {
            $breakdown['total'] = round($breakdown['total']);
        }

        return response()->json([
            'toolsDiscount' => $breakdown['tools_discount'],
            'annualDiscount' => $breakdown['annual_discount'],
            'total' => $breakdown['total']
        ]);
    }

    public function checkoutKashier(Request $request)
    {
        $request->validate([
            'plan_id'       => 'nullable|exists:plans,id',
            'items'         => 'nullable|array',
            'billing_cycle' => 'required|string',
            'is_new_system' => 'nullable|boolean',
        ]);

        $user = Auth::user();
        $this->subscriptionService->validateAddonParents($request->items, $user);
        $isNewSystem = $request->input('is_new_system', false);
        
        $usdCurrency = \App\Models\Currency::where('currency', 'USD')->first();
        $usdCurrencyId = $usdCurrency ? $usdCurrency->id : 1;
        
        $egpCurrency = \App\Models\Currency::where('currency', 'EGP')->first();
        $egpCurrencyId = $egpCurrency ? $egpCurrency->id : 1;
        
        $userCurrencyId = $user->currency_id ?: $egpCurrencyId;
        $userCurrency = \App\Models\Currency::find($userCurrencyId);
        $currencyCode = $userCurrency ? $userCurrency->currency : 'USD';

        $billingCycle = $request->input('billing_cycle', '1_year');
        $multiplier = 1;
        $days = 30; 
        
        if ($billingCycle === '3_months') {
            $multiplier = 0.25;
            $days = 90;
        } elseif ($billingCycle === '6_months') {
            $multiplier = 0.5;
            $days = 180;
        } elseif ($billingCycle === '1_year') {
            $multiplier = 10;
            $days = 365;
        }

        if ($request->has('items') && count($request->items) > 0) {
            $base_plan_amount = $this->subscriptionService->calculateCustomPriceBackend($request->items, $request->input('billing_cycle', '1_year'), $userCurrencyId);
        } else {
            return back()->withErrors(['error' => 'No modules selected.']);
        }

        $plan_amount = $currencyCode === 'EGP' ? round($base_plan_amount) : psychological_price($base_plan_amount);

        $paymentUrl = \App\Helpers\KashierHelper::buildSubscriptionPaymentUrl(
            $plan_amount,
            $user->id,
            $user->name,
            $user->email,
            null,
            $currencyCode,
            $billingCycle,
            $days,
            $request->items ?? [],
            $isNewSystem
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
                $days = $metadata['days'] ?? 365;
                $isNewSystem = $metadata['is_new_system'] ?? true;

                if ($userId && $trxId && $amountPaid > 0) {
                    $user = \App\Models\User::find($userId);
                    
                    if ($user) {
                        // Idempotency check
                        $reason = "Subscription modules via Kashier online payment (Trx: $trxId)";
                        $alreadyProcessed = Transaction::where('user_id', $user->id)
                            ->where('reason', $reason)
                            ->exists();

                        if (!$alreadyProcessed) {
                            try {
                                DB::transaction(function () use ($user, $amountPaid, $reason, $days, $isNewSystem, $metadata) {
                                    $userTenant = \Modules\ERP\Models\Tenant::where('user_id', $user->id)->first();
                                    if ($isNewSystem && !$userTenant) {
                                        $tenantName = explode(' ', $user->name)[0] . ' Workspace ' . substr(uniqid(), -4);
                                        $tenant = \Modules\ERP\Models\Tenant::create([
                                            'user_id' => $user->id,
                                            'name' => $tenantName,
                                            'status' => 'active',
                                            'base_currency_id' => $user->currency_id ?: 1, // fallback to USD
                                        ]);
                                        $userTenant = $tenant;
                                    }

                                    $user->add_balance($amountPaid, $reason, 'received');
                                    
                                    // Deduct balance for plan
                                    \App\Helpers\TimerHelper::instance()->addUsed($user, $amountPaid, 'Subscribe to modules');

                                    // Save capabilities
                                    $items = $metadata['items'] ?? [];
                                    if (is_array($items) && !empty($items)) {
                                        foreach ($items as $item) {
                                            $expiry = \Carbon\Carbon::now()->addDays((int) $days);
                                            
                                            // Check if they already own it, extend expiry
                                            $existing = \App\Models\UserSubscription::where('user_id', $user->id)->where('object', $item)->first();
                                            if ($existing && $existing->status === 'active' && \Carbon\Carbon::parse($existing->expires_at)->isFuture()) {
                                                $expiry = \Carbon\Carbon::parse($existing->expires_at)->addDays((int) $days);
                                            }

                                            \App\Models\UserSubscription::updateOrCreate(
                                                ['user_id' => $user->id, 'object' => $item],
                                                [
                                                    'status' => 'active',
                                                    'started_at' => now(),
                                                    'expires_at' => $expiry,
                                                    'auto_renew' => true
                                                ]
                                            );

                                            // Also update tenant_features
                                            if ($userTenant) {
                                                \App\Models\TenantFeature::updateOrCreate(
                                                    ['tenant_id' => $userTenant->id, 'feature_key' => $item],
                                                    [
                                                        'module' => str_starts_with($item, 'crm') ? 'crm' : (str_starts_with($item, 'erp') ? 'erp' : (str_starts_with($item, 'tool') ? 'tools' : 'booking')),
                                                        'expires_at' => $expiry
                                                    ]
                                                );
                                            }
                                        }
                                    }
                                });
                                \Illuminate\Support\Facades\Log::info("Kashier subscription processed successfully for User $userId");
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
        return redirect()->route('subscriptions.manage')->with('success', __('general.payment_successful'));
    }

    public function kashierFailure(Request $request)
    {
        return redirect()->route('subscriptions.plans')->with('error', __('general.payment_failed_or_was_canceled'));
    }

    /**
     * Show "My Subscriptions" management page.
     */
    public function manage(Request $request)
    {
        $user = Auth::user();
        
        $subscriptions = [];
        
        $userSubs = \App\Models\UserSubscription::where('user_id', $user->id)
            ->whereIn('status', ['active', 'expired', 'cancelled'])
            ->get();
            
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

        $convertPrice = function($egpPrice) use ($egpRate, $rate, $currencyCode) {
            if ($currencyCode === 'EGP') {
                return round($egpPrice);
            }
            $usdPrice = $egpPrice / $egpRate;
            $converted = $usdPrice * $rate;
            return psychological_price($converted);
        };

        $serviceItems = app(\App\Services\PricingService::class)->getServiceItems($convertPrice);
        
        if ($userSubs->count() > 0) {
            foreach ($userSubs as $sub) {
                $item = collect($serviceItems)->firstWhere('id', $sub->object);
                $monthlyPrice = $item['monthly_price'] ?? 0;

                $subscriptions[] = [
                    'id'            => $sub->id,
                    'plan_name'     => $item['name'] ?? ucfirst(str_replace('-', ' ', $sub->object)),
                    'plan_slug'     => $sub->object,
                    'billing_cycle' => 'Module',
                    'amount'        => $monthlyPrice,
                    'currency'      => $user->currency_name(),
                    'status'        => $sub->status,
                    'started_at'    => \Carbon\Carbon::parse($sub->started_at)->format('M d, Y'),
                    'expires_at'    => \Carbon\Carbon::parse($sub->expires_at)->format('M d, Y'),
                    'auto_renew'    => (bool) $sub->auto_renew,
                    'custom_items'  => [$sub->object],
                    'is_custom'     => false,
                ];
            }
        }

        $invoices = []; // SubscriptionInvoice might not exist in legacy DB

        return Inertia::render('Client/Subscriptions/Manage', [
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
        $request->validate(['id' => 'required|exists:user_subscriptions,id']);
        $sub = \App\Models\UserSubscription::where('id', $request->id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $sub->update(['auto_renew' => false]);

        return back()->with('success', __('general.your_subscription_auto_renewal_has_been_cancelled'));
    }

    /**
     * Renew subscription using Wallet Balance.
     */
    public function renew(Request $request)
    {
        $request->validate(['id' => 'required|exists:user_subscriptions,id']);
        $user = Auth::user();
        $sub = \App\Models\UserSubscription::where('id', $request->id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        // get pricing
        $pricingService = app(\App\Services\PricingService::class);
        $serviceItems = $pricingService->getServiceItems();
        $item = collect($serviceItems)->firstWhere('id', $sub->object);
        if (!$item) {
            return back()->withErrors(['error' => 'Module no longer available.']);
        }

        $price = $item['monthly_price'] ?? 0;
        $userBalance = (float) $user->available_balance();
        
        $proratedDays = null;
        if ($userBalance < $price) {
            if ($userBalance > 0 && $price > 0) {
                $proratedDays = floor(($userBalance / $price) * 30);
                if ($proratedDays >= 1) {
                    $price = ($proratedDays / 30) * $price;
                } else {
                    return back()->withErrors(['error' => 'Insufficient balance for even a 1-day proration.']);
                }
            } else {
                return back()->withErrors(['error' => 'Insufficient balance to renew.']);
            }
        }

        try {
            DB::transaction(function () use ($user, $sub, $price, $item, $proratedDays) {
                if ($price > 0) {
                    $itemName = $item['name'] ?? ucfirst($sub->object);
                    $desc = $proratedDays ? "Manual Prorated Subscription Renewal for {$proratedDays} days: " : "Manual Subscription Renewal: ";
                    $user->add_balance(-1 * $price, $desc . $itemName, 'used');
                }

                $newExpiresAt = $sub->expires_at && \Carbon\Carbon::parse($sub->expires_at)->isFuture() 
                    ? \Carbon\Carbon::parse($sub->expires_at)
                    : \Carbon\Carbon::now();
                
                if ($proratedDays !== null) {
                    $newExpiresAt->addDays($proratedDays);
                } else {
                    $newExpiresAt->addMonth();
                }

                $sub->update([
                    'status' => 'active',
                    'expires_at' => $newExpiresAt,
                    'auto_renew' => true
                ]);

                $tenant = \Modules\ERP\Models\Tenant::where('user_id', $user->id)->first();
                if ($tenant) {
                    \App\Models\TenantFeature::updateOrCreate(
                        ['tenant_id' => $tenant->id, 'feature_key' => $sub->object],
                        [
                            'module' => str_starts_with($sub->object, 'crm') ? 'crm' : (str_starts_with($sub->object, 'erp') ? 'erp' : (str_starts_with($sub->object, 'tool') ? 'tools' : 'booking')),
                            'expires_at' => $newExpiresAt
                        ]
                    );
                }
            });

            if ($proratedDays) {
                return back()->with('success', "Subscription partially renewed for {$proratedDays} days.");
            }
            return back()->with('success', __('general.subscription_renewed_successfully'));
        } catch (\Exception $e) {
            Log::error('Manual renewal failed: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to renew: ' . $e->getMessage()]);
        }
    }
}

