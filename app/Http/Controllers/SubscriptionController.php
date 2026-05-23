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
        $userCurrency = $user->currency ?? 1; // Legacy uses currency ID

        $plans = Plan::where('plan_status', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $wallet = ['id' => null, 'balance' => (float)$user->user_balance, 'currency' => $userCurrency];

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
            'plans' => $plans->map(fn ($plan) => [
                'id'               => $plan->id,
                'slug'             => \Str::slug($plan->plan_name),
                'name'             => $plan->plan_name,
                'description'      => $plan->plan_description,
                'monthly_price'    => $plan->current_plan_price(),
                'yearly_price'     => $plan->current_plan_price() * 10, // Dummy estimate for UI
                'prices'           => [
                    '1_month' => $plan->current_plan_price(),
                ],
                'included_modules' => ['erp', 'freelance'],
                'included_tools'   => [],
                'features'         => [],
                'is_custom'        => false,
            ]),
            'serviceItems' => [],
            'activeSubscription' => $activeSub,
            'walletBalance' => (float) ($wallet['balance']),
            'currency' => $wallet['currency'],
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
        
        $current_plan = null;
        $plan_amount = $plan->current_plan_price();
        $current_plan_remaining_price = 0;
        
        if ($user->plan_id != null) {
            $current_plan = Plan::find($user->plan_id);
            if ($current_plan) {
                // calc the remaining days
                $current_plan_remaining_days = (strtotime($user->subscription_date) - strtotime(date('Y-m-d'))) / (60 * 60 * 24);
                $current_plan_remaining_days = round($current_plan_remaining_days);
                if ($current_plan_remaining_days > 0) {
                    $current_plan_remaining_price = ($current_plan->plan_price / $current_plan->plan_duration) * $current_plan_remaining_days;
                }
            }
        }

        if (($user->user_balance + $current_plan_remaining_price) < $plan_amount) {
            return back()->withErrors(['error' => 'Insufficient balance.']);
        }

        try {
            DB::transaction(function () use ($user, $plan, $plan_amount, $current_plan_remaining_price, $current_plan) {
                if ($current_plan != null && $current_plan_remaining_price > 0) {
                    $user->add_balance($current_plan_remaining_price, 'Refund for remaining days of ' . $current_plan->plan_name . ' plan', 'refund', $current_plan->plan_currency);
                }

                \App\Helper\TimerHelper::instance()->addUsed($user, $plan_amount, 'Subscribe to ' . $plan->plan_name . ' plan');

                $user->subscription_plan = $plan->plan_name;
                $user->subscription_date = date('Y-m-d', strtotime('+' . $plan->plan_duration . ' day'));
                $user->plan_id = $plan->id;
                $user->subscription_force = 1;
                $user->save();
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
        return back()->withErrors(['error' => 'Please use Wallet balance. Kashier direct subscription is disabled pending legacy mapping.']);
    }

    public function webhook(Request $request)
    {
        return response()->json(['status' => 'ignored', 'message' => 'Legacy webhook mapping required.']);
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
                'currency'      => $user->currency,
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
            'currency'      => $user->currency,
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
