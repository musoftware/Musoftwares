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
        $data = $this->subscriptionService->getPlansPageData(Auth::user());
        return Inertia::render('Client/Subscriptions/Plans', $data);
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
        
        $currencyDetails = $this->subscriptionService->getUserCurrencyDetails($user);
        $billingCycle = $request->input('billing_cycle', '1_year');
        $billing = $this->subscriptionService->getBillingCycleDetails($billingCycle);

        if ($request->has('items') && count($request->items) > 0) {
            $base_plan_amount = $this->subscriptionService->calculateCustomPriceBackend($request->items, $billingCycle, $currencyDetails['currencyId']);
        } else {
            return back()->withErrors(['error' => 'No modules selected.']);
        }

        $plan_amount = $currencyDetails['currencyCode'] === 'EGP' ? round($base_plan_amount) : psychological_price($base_plan_amount);
        $isNewSystem = $request->input('is_new_system', true);
        
        if ($user->user_balance < $plan_amount) {
            return back()->withErrors(['error' => 'Insufficient balance.']);
        }

        try {
            $this->subscriptionService->processSubscription($user, $plan_amount, $billing['days'], $request->items ?? [], $isNewSystem, 'Subscribe to modules', 'wallet_subscribe');

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
        $currencyDetails = $this->subscriptionService->getUserCurrencyDetails($user, $request->ip());

        $items = $request->input('items', []);
        $billingCycle = $request->input('billing_cycle', '1_month');

        if (empty($items)) {
            return response()->json([
                'toolsDiscount' => 0,
                'annualDiscount' => 0,
                'total' => 0
            ]);
        }

        $breakdown = $this->subscriptionService->calculateCustomPriceBackend($items, $billingCycle, $currencyDetails['currencyId'], true);

        if ($currencyDetails['currencyCode'] !== 'EGP') {
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
        
        $currencyDetails = $this->subscriptionService->getUserCurrencyDetails($user);
        $billingCycle = $request->input('billing_cycle', '1_year');
        $billing = $this->subscriptionService->getBillingCycleDetails($billingCycle);

        if ($request->has('items') && count($request->items) > 0) {
            $base_plan_amount = $this->subscriptionService->calculateCustomPriceBackend($request->items, $billingCycle, $currencyDetails['currencyId']);
        } else {
            return back()->withErrors(['error' => 'No modules selected.']);
        }

        $plan_amount = $currencyDetails['currencyCode'] === 'EGP' ? round($base_plan_amount) : psychological_price($base_plan_amount);

        $paymentUrl = \App\Helpers\KashierHelper::buildSubscriptionPaymentUrl(
            $plan_amount,
            $user->id,
            $user->name,
            $user->email,
            null,
            $currencyDetails['currencyCode'],
            $billingCycle,
            $billing['days'],
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
                                $this->subscriptionService->processSubscription($user, $amountPaid, $days, $metadata['items'] ?? [], $isNewSystem, $reason, 'webhook_received');
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
        $data = $this->subscriptionService->getManagePageData(Auth::user());
        return Inertia::render('Client/Subscriptions/Manage', $data);
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
            $this->subscriptionService->renewSubscription($user, $sub, $price, $item, $proratedDays);

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

