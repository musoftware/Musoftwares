<?php

namespace App\Services;

use App\Models\User;
use App\Models\Plan;
use App\Models\UserSubscription;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Transaction;
use Illuminate\Validation\ValidationException;

class SubscriptionService
{
    /**
     * Get the user's active plan (if any).
     */
    public function getActiveSubscription(User $user, ?string $module = null)
    {
        if ($user->hasSubscription()) {
            return $user->plan;
        }
        return null;
    }

    /**
     * Check if a user has active access to a module.
     */
    public function hasActiveSubscription(User $user, string $module): bool
    {
        // Removed Admin bypass: Admins must subscribe to modules to access them.

        // Check specific module subscription in user_subscriptions
        if ($user->hasModuleSubscription($module)) {
            return true;
        }

        // Legacy fallback: old plan_id system gave access to everything
        if ($user->plan_id && $user->subscription_date) {
            return \Carbon\Carbon::parse($user->subscription_date)->isFuture();
        }

        return false;
    }

    /**
     * Check if a user has active access to a specific tool.
     */
    public function hasAccessToTool(User $user, string $toolSlug): bool
    {
        // 1. Check if tool is free
        $tool = collect(config('tools'))->firstWhere('slug', $toolSlug);
        if ($tool && ($tool['is_free'] ?? false)) {
            return true;
        }

        // 2. Points-based tools are never free
        if (in_array($toolSlug, ['freelance', 'facebook-publisher'])) {
            return false;
        }

        // 3. Check if user specifically bought this tool (new module system)
        if ($user->hasModuleSubscription('tool-' . $toolSlug)) {
            return true;
        }

        if ($tool && $user->hasModuleSubscription('tool-' . $tool['guid'])) {
            return true;
        }

        if ($tool && $user->hasModuleSubscription('TOOL-' . strtoupper($tool['guid']))) {
            return true;
        }

        // 4. Check if user has the global 'tools' module subscription (Platform Pass)
        if ($user->hasModuleSubscription('tools')) {
            return true;
        }

        // 5. Fallback: old ToolSubscription model
        if ($tool && class_exists(\Modules\Tools\Models\ToolSubscription::class)) {
            return \Modules\Tools\Models\ToolSubscription::where('user_id', $user->id)
                ->where('tool_guid', $tool['guid'])
                ->where('status', 'active')
                ->exists();
        }

        return false;
    }

    /**
     * Check if user has ANY active platform subscription.
     */
    public function hasAnySubscription(User $user): bool
    {
        // Removed Admin bypass

        return $user->hasSubscription();
    }

    public function createSubscription(User $user, Plan $plan, string $cycle, array $customItems = []): UserSubscription
    {
        $amount = $plan->priceFor($cycle); // Assuming Plan has this method or we adjust it

        return UserSubscription::create([]);
    }

    /**
     * Calculate the proration value for an existing subscription.
     * This calculates the remaining unused value of the current subscription.
     */
    public function calculateUpgradeProration(UserSubscription $current, float $newPrice, string $cycle): float
    {
        $now = Carbon::now();
        if (!$current->expires_at || Carbon::parse($current->expires_at)->isPast()) {
            return 0.0;
        }

        $started = $current->started_at ? Carbon::parse($current->started_at) : $now;
        $expires = Carbon::parse($current->expires_at);
        $totalDays = $started->diffInDays($expires);
        
        if ($totalDays <= 0) {
            return 0.0;
        }

        $daysRemaining = $now->diffInDays($expires);
        if ($daysRemaining <= 0) {
            return 0.0;
        }

        // We assume the old price is roughly proportional to the new price, 
        // or we can just fetch the old item price if we had a PricingService instance here.
        // For now, we return the prorated percentage of the remaining days.
        $prorationPercentage = $daysRemaining / $totalDays;
        
        // This is a simplified proration assuming the user pays the $newPrice and gets credit for unused days.
        // To be completely accurate we'd need the original transaction amount, but this approximates the unused portion.
        return round($newPrice * $prorationPercentage, 2);
    }

    /**
     * Get subscription limits for a user and module.
     */
    public function getAvailableModules()
    {
        return Service::where('type', 'module')->where('is_active', true)->orderBy('sort_order')->get();
    }

    public function getLimits(User $user, string $module): array
    {


        if (!$user->hasSubscription()) {
            if ($module === 'freelance') {
                return ['connects' => 20, 'commission_rate' => 10.0];
            }
            if ($module === 'erp') {
                return ['projects' => -1, 'invoices' => -1, 'tasks' => -1, 'team_members' => -1];
            }
            return ['projects' => 0, 'invoices' => 0, 'tasks' => 0, 'team_members' => 0];
        }

        if ($module === 'erp') {
            return ['projects' => -1, 'invoices' => -1, 'tasks' => -1, 'team_members' => 10];
        }

        if ($module === 'freelance') {
            return ['connects' => 120, 'commission_rate' => 5.0];
        }

        return [];
    }

    public function getAvailablePlans()
    {
        return Plan::where('is_active', true)->orderBy('sort_order')->get();
    }

    /**
     * Enforce a feature limit before action.
     */
    public function isWithinLimit(User $user, string $module, string $limitKey, int $currentCount): bool
    {


        $limits = $this->getLimits($user, $module);

        if (!isset($limits[$limitKey])) {
            return true;
        }

        $maxVal = $limits[$limitKey];

        if ($maxVal === -1) {
            return true;
        }

        return $currentCount < $maxVal;
    }

    public function calculateCustomPriceBackend($selectedItems, $billingCycle, $currencyId, $returnArray = false)
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
        
        $configTools = config('tools', []);
        $baseMonthlyEGP = 0;
        $toolsMonthlyEGP = [];

        foreach ($selectedItems as $item) {
            if (isset($basePricesEGP[$item])) {
                $baseMonthlyEGP += ($basePricesEGP[$item] / 10);
            } elseif (str_starts_with($item, 'tool-')) {
                $guid = preg_replace('/^tool-/', '', $item);
                $tool = $configTools[$guid] ?? null;
                $isFree = $tool['is_free'] ?? false;
                if (!$isFree && $tool) {
                    $toolMonthlyPrice = 100; // Fallback
                    if (isset($tool['plans']) && is_array($tool['plans']) && count($tool['plans']) > 0) {
                        $firstPlan = reset($tool['plans']);
                        if (isset($firstPlan['price_monthly'])) {
                            $toolMonthlyPrice = $firstPlan['price_monthly'];
                        }
                    }
                    $toolsMonthlyEGP[] = $toolMonthlyPrice;
                }
            }
        }

        $months = 1;
        $multiplier = 1;
        if ($billingCycle === '6_months') {
            $months = 6;
            $multiplier = 6;
        } elseif ($billingCycle === '1_year') {
            $months = 12;
            $multiplier = 10; // 2 months free
        }

        // Apply progressive tool volume discount
        rsort($toolsMonthlyEGP);
        $toolsBaseTotalMonthlyEGP = 0;
        $toolsDiscountedTotalMonthlyEGP = 0;

        foreach ($toolsMonthlyEGP as $index => $price) {
            $toolsBaseTotalMonthlyEGP += $price;
            $discountPercent = min(50, $index * 10);
            $toolsDiscountedTotalMonthlyEGP += $price * (1 - ($discountPercent / 100));
        }

        $subtotalMonthlyEGP = $baseMonthlyEGP + $toolsDiscountedTotalMonthlyEGP;
        
        $originalTotalEGP = ($baseMonthlyEGP + $toolsBaseTotalMonthlyEGP) * $months;
        $totalEGP = $subtotalMonthlyEGP * $multiplier;
        
        $toolsDiscountEGP = ($toolsBaseTotalMonthlyEGP - $toolsDiscountedTotalMonthlyEGP) * $months;
        $annualDiscountEGP = ($subtotalMonthlyEGP * $months) - ($subtotalMonthlyEGP * $multiplier);

        $toTargetCurrency = function($amountEgp) use ($egpRate, $rate) {
            $usd = $amountEgp / $egpRate;
            return $usd * $rate;
        };

        if ($returnArray) {
            return [
                'subtotal' => $toTargetCurrency($subtotalMonthlyEGP * $months),
                'tools_discount' => $toTargetCurrency($toolsDiscountEGP),
                'annual_discount' => $toTargetCurrency($annualDiscountEGP),
                'total' => $toTargetCurrency($totalEGP),
            ];
        }

        return $toTargetCurrency($totalEGP);
    }

    public function validateAddonParents($items, $user = null)
    {
        if (!$items || !is_array($items)) return;

        $addonsConfig = config('saas.addons', []);
        foreach ($items as $item) {
            if (isset($addonsConfig[$item])) {
                $parent = $addonsConfig[$item]['parent'];
                
                // Check if parent is in the cart
                $inCart = in_array($parent, $items);
                
                // Check if user already owns the parent
                $alreadyOwned = false;
                if ($user && !$inCart) {
                    $alreadyOwned = \App\Models\UserSubscription::where('user_id', $user->id)
                        ->where('object', $parent)
                        ->where('status', 'active')
                        ->where('expires_at', '>', now())
                        ->exists();
                }

                if (!$inCart && !$alreadyOwned) {
                    throw ValidationException::withMessages([
                        'error' => "You cannot subscribe to {$addonsConfig[$item]['name']} without its parent module."
                    ]);
                }
            }
        }
    }

    public function processSubscription(User $user, float $amount, int $days, array $items, bool $isNewSystem, string $reason, string $action = 'wallet_subscribe')
    {
        return DB::transaction(function () use ($user, $amount, $days, $items, $isNewSystem, $reason, $action) {
            $userTenant = \Modules\ERP\Models\Tenant::where('user_id', $user->id)->first();
            if ($isNewSystem && !$userTenant) {
                $tenantName = explode(' ', $user->name)[0] . ' Workspace ' . substr(uniqid(), -4);
                $usdCurrencyId = \App\Models\Currency::where('currency', 'USD')->value('id') ?: 1;
                $tenant = \Modules\ERP\Models\Tenant::create([
                    'user_id' => $user->id,
                    'name' => $tenantName,
                    'status' => 'active',
                    'base_currency_id' => $user->currency_id ?: $usdCurrencyId,
                ]);
                $userTenant = $tenant;
            }

            if ($action === 'webhook_received') {
                $user->add_balance($amount, $reason, 'received');
                \App\Helpers\TimerHelper::instance()->addUsed($user, $amount, 'Subscribe to modules');
            } elseif ($action === 'wallet_subscribe' && $amount > 0) {
                $user->add_balance(-1 * $amount, 'Subscribe to modules', 'used');
            }

            if (!empty($items) && is_array($items)) {
                foreach ($items as $item) {
                    $expiry = Carbon::now()->addDays((int) $days);
                    
                    // Check if they already own it, extend expiry
                    $existing = \App\Models\UserSubscription::where('user_id', $user->id)->where('object', $item)->first();
                    if ($existing && $existing->status === 'active' && Carbon::parse($existing->expires_at)->isFuture()) {
                        $expiry = Carbon::parse($existing->expires_at)->addDays((int) $days);
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
    }

    public function renewSubscription(User $user, UserSubscription $sub, float $price, array $item, ?int $proratedDays)
    {
        return DB::transaction(function () use ($user, $sub, $price, $item, $proratedDays) {
            if ($price > 0) {
                $itemName = $item['name'] ?? ucfirst($sub->object);
                $desc = $proratedDays ? "Manual Prorated Subscription Renewal for {$proratedDays} days: " : "Manual Subscription Renewal: ";
                $user->add_balance(-1 * $price, $desc . $itemName, 'used');
            }

            $newExpiresAt = $sub->expires_at && Carbon::parse($sub->expires_at)->isFuture() 
                ? Carbon::parse($sub->expires_at)
                : Carbon::now();
            
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
    }

    public function getUserCurrencyDetails($user, $ip = null)
    {
        $usdCurrency = \App\Models\Currency::where('currency', 'USD')->first();
        $usdCurrencyId = $usdCurrency ? $usdCurrency->id : 1;
        
        $egpCurrency = \App\Models\Currency::where('currency', 'EGP')->first();
        $egpCurrencyId = $egpCurrency ? $egpCurrency->id : 1;
        
        $currencyId = null;
        if ($user && $user->currency_id) {
            $currencyId = $user->currency_id;
        } elseif ($ip) {
            $geoService = app(\App\Services\IpGeolocationService::class);
            $ipCurrencyCode = $geoService->getCurrencyCodeForIp($ip);
            if ($ipCurrencyCode) {
                $ipCurrency = \App\Models\Currency::where('currency', $ipCurrencyCode)->first();
                if ($ipCurrency) {
                    $currencyId = $ipCurrency->id;
                }
            }
        }
        
        if (!$currencyId) {
            $currencyId = $user ? ($user->currency_id ?: $egpCurrencyId) : $usdCurrencyId;
        }

        $userCurrency = \App\Models\Currency::find($currencyId);
        $currencyCode = $userCurrency ? $userCurrency->currency : 'USD';
        
        $rate = 1.0;
        if ($usdCurrency && $currencyId && $usdCurrency->id != $currencyId) {
            $rate = \App\Models\CurrenciesExchange::RateToday(1, $usdCurrency->id, $currencyId);
        }

        $egpRate = 50; // Fallback
        if ($usdCurrency && $egpCurrencyId) {
            $egpRate = \App\Models\CurrenciesExchange::RateToday(1, $usdCurrency->id, $egpCurrencyId) ?: 50;
        }

        return [
            'usdCurrencyId' => $usdCurrencyId,
            'egpCurrencyId' => $egpCurrencyId,
            'currencyId' => $currencyId,
            'currencyCode' => $currencyCode,
            'rate' => $rate,
            'egpRate' => $egpRate
        ];
    }

    public function getConvertPriceClosure($currencyDetails)
    {
        return function($egpPrice) use ($currencyDetails) {
            if ($currencyDetails['currencyCode'] === 'EGP') {
                return round($egpPrice);
            }
            $usdPrice = $egpPrice / $currencyDetails['egpRate'];
            $converted = $usdPrice * $currencyDetails['rate'];
            return psychological_price($converted);
        };
    }

    public function getBillingCycleDetails(string $billingCycle)
    {
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
        return ['multiplier' => $multiplier, 'days' => $days];
    }

    public function getPlansPageData(User $user)
    {
        $currencyDetails = $this->getUserCurrencyDetails($user);
        $convertPrice = $this->getConvertPriceClosure($currencyDetails);

        $pricingService = app(\App\Services\PricingService::class);
        $serviceItems = $pricingService->getServiceItems($convertPrice);

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
            $closestExpiry = $userSubs->min('expires_at');
            $activeSub = [
                'id'            => $user->id,
                'status'        => 'active',
                'expires_at'    => $closestExpiry ? Carbon::parse($closestExpiry)->format('M d, Y') : '-',
                'auto_renew'    => false,
                'owned_features' => $ownedFeatures,
            ];
        }

        return [
            'plans' => [],
            'serviceItems' => $serviceItems,
            'activeSubscription' => $activeSub,
            'walletBalance' => (float) $user->available_balance(),
            'currency' => $currencyDetails['currencyCode'],
            'proratedRefund' => 0.0,
        ];
    }

    public function getManagePageData(User $user)
    {
        $userSubs = \App\Models\UserSubscription::where('user_id', $user->id)
            ->whereIn('status', ['active', 'expired', 'cancelled'])
            ->get();
            
        $currencyDetails = $this->getUserCurrencyDetails($user);
        $convertPrice = $this->getConvertPriceClosure($currencyDetails);
        $serviceItems = app(\App\Services\PricingService::class)->getServiceItems($convertPrice);
        
        $subscriptions = [];
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

        return [
            'subscriptions' => $subscriptions,
            'invoices'      => [],
            'walletBalance' => (float) $user->user_balance,
            'currency'      => $user->currency_name(),
        ];
    }
}
