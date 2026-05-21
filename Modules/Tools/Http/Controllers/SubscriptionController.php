<?php

namespace Modules\Tools\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Tools\Models\ToolLicense;
use Modules\Tools\Models\ToolSubscription;

class SubscriptionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Billing overview — all the user's tool subscriptions.
     */
    public function billing(): Response
    {
        $subscriptions = ToolSubscription::where('user_id', auth()->id())
            ->latest()
            ->get()
            ->map(function ($sub) {
                $tool = collect(config('tools'))->firstWhere('guid', $sub->tool_guid);
                $plan = $tool ? collect($tool['plans'] ?? [])->firstWhere('guid', $sub->plan_guid) : null;
                
                return [
                    'id'           => $sub->id,
                    'tool'         => [
                        'slug'     => $tool['slug'] ?? 'unknown',
                        'title'    => $tool['title'] ?? 'Unknown Tool',
                        'icon_url' => $tool['icon_url'] ?? null,
                        'category' => $tool['category'] ?? 'unknown',
                    ],
                    'plan_name'    => $plan['name'] ?? 'N/A',
                    'billing_cycle' => $sub->billing_cycle,
                    'amount_paid'  => $sub->amount_paid,
                    'currency'     => $sub->currency,
                    'status'       => $sub->status,
                    'is_active'    => $sub->isActive(),
                    'starts_at'    => $sub->starts_at->toDateString(),
                    'expires_at'   => $sub->expires_at?->toDateString(),
                ];
            });

        return Inertia::render('Tools/Billing', [
            'subscriptions' => $subscriptions,
        ]);
    }

    /**
     * Show subscription checkout page for a plan.
     */
    public function checkout(string $slug, string $planGuid): Response
    {
        $tool = collect(config('tools'))->firstWhere('slug', $slug);
        if (!$tool || !($tool['is_active'] ?? false)) {
            abort(404);
        }

        $plan = collect($tool['plans'] ?? [])->firstWhere('guid', $planGuid);
        if (!$plan) {
            abort(404);
        }

        // Check existing active subscription
        $existing = ToolSubscription::where('user_id', auth()->id())
            ->where('tool_guid', $tool['guid'])
            ->where('status', 'active')
            ->first();

        $walletBalance = auth()->user()->walletBalance ?? 0;

        return Inertia::render('Tools/Subscribe', [
            'tool'          => [
                'slug'    => $tool['slug'],
                'title'   => $tool['title'],
                'icon_url' => $tool['icon_url'] ?? null,
            ],
            'plan'          => [
                'id'            => $plan['guid'],
                'name'          => $plan['name'],
                'price_monthly' => $plan['price_monthly'],
                'price_yearly'  => $plan['price_yearly'],
                'features'      => $plan['features'] ?? [],
            ],
            'walletBalance' => $walletBalance,
            'hasExisting'   => (bool) $existing,
        ]);
    }

    /**
     * Process subscription — free plans activate instantly, paid plans use wallet/kashier.
     */
    public function subscribe(Request $request, string $slug, string $planGuid): RedirectResponse
    {
        $tool = collect(config('tools'))->firstWhere('slug', $slug);
        if (!$tool || !($tool['is_active'] ?? false)) {
            abort(404);
        }

        $plan = collect($tool['plans'] ?? [])->firstWhere('guid', $planGuid);
        if (!$plan) {
            abort(404);
        }

        $price = $request->input('billing_cycle') === 'yearly'
            ? $plan['price_yearly']
            : $plan['price_monthly'];

        $isFree = $price <= 0;

        $request->validate([
            'billing_cycle'  => ['required', 'in:monthly,yearly'],
            'payment_method' => $isFree ? [] : ['required', 'in:wallet,kashier'],
        ]);

        // Prevent duplicate active subscription
        $existing = ToolSubscription::where('user_id', auth()->id())
            ->where('tool_guid', $tool['guid'])
            ->where('status', 'active')
            ->first();

        if ($existing) {
            return back()->with('error', 'You already have an active subscription to this tool.');
        }

        $expiresAt = now()->addMonth($request->billing_cycle === 'yearly' ? 12 : 1);

        DB::transaction(function () use ($tool, $plan, $request, $price, $expiresAt, $isFree) {
            if (!$isFree && $request->payment_method === 'wallet') {
                $user   = auth()->user();
                $wallet = $user->wallet ?? $user->createWallet();
                if ($wallet->balance < $price) {
                    abort(422, 'Insufficient wallet balance.');
                }
                $wallet->decrement('balance', $price);
            }
            // Kashier: handled client-side with webhook — not deducted here.

            $sub = ToolSubscription::create([
                'user_id'              => auth()->id(),
                'tool_guid'            => $tool['guid'],
                'plan_guid'            => $plan['guid'],
                'billing_cycle'        => $request->billing_cycle,
                'amount_paid'          => $price,
                'currency'             => 'USD',
                // Free plans + wallet plans activate immediately; kashier waits for webhook
                'status'               => ($isFree || $request->payment_method === 'wallet') ? 'active' : 'pending',
                'payment_method'       => $isFree ? 'free' : $request->payment_method,
                'starts_at'            => now(),
                'expires_at'           => $expiresAt,
            ]);

            if ($sub->status === 'active') {
                $sub->issueLicense();
            }
        });

        return redirect()->route('tools.show', $slug)
            ->with('success', $isFree
                ? '✓ Access granted! The runtime will sync this tool automatically.'
                : '✓ Subscription activated! Your license key is ready.');
    }


    /**
     * Cancel a subscription.
     */
    public function cancel(int $id): RedirectResponse
    {
        $sub = ToolSubscription::where('user_id', auth()->id())->findOrFail($id);
        $sub->update([
            'status'       => 'cancelled',
            'cancelled_at' => now(),
        ]);

        return back()->with('success', 'Subscription cancelled. Access continues until ' . $sub->expires_at?->format('M d, Y') . '.');
    }
}
