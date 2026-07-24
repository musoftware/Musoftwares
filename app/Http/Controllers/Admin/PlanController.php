<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Plan\StoreUserSubscriptionRequest;
use App\Models\AdminSettings;
use App\Models\CurrenciesExchange;
use App\Models\Currency;
use App\Models\User;
use App\Models\UserSubscription;
use App\Services\PricingService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlanController extends Controller
{
    public function __construct(
        protected PricingService $pricingService
    ) {}

    public function index(Request $request): Response
    {
        $businessCurrencyId = AdminSettings::business_currency();
        $businessCurrency = Currency::find($businessCurrencyId);

        $egpCurrency = Currency::where('currency', 'EGP')->first();

        $convertPrice = function ($price) use ($egpCurrency, $businessCurrency) {
            if ($egpCurrency && $businessCurrency && $egpCurrency->id != $businessCurrency->id) {
                return CurrenciesExchange::RateToday($price, $egpCurrency->id, $businessCurrency->id);
            }

            return $price;
        };

        $items = collect($this->pricingService->getServiceItems($convertPrice))->keyBy('id');

        $users = User::whereHas('subscriptions', function ($q) {
            $q->where('status', 'active')
                ->where('expires_at', '>', now());
        })
            ->with(['subscriptions' => function ($q) {
                $q->where('status', 'active')
                    ->where('expires_at', '>', now());
            }])
            ->paginate(15);

        $users->getCollection()->transform(function ($user) use ($items) {
            $firstExpireDate = null;
            $totalMonthlyPrice = 0;
            $services = [];

            foreach ($user->subscriptions as $sub) {
                $item = $items->get($sub->object);
                if ($item) {
                    $totalMonthlyPrice += $item['monthly_price'];
                    $services[] = [
                        'id' => $sub->id,
                        'name' => $item['name'],
                        'icon' => $item['icon'],
                        'monthly_price' => $item['monthly_price'],
                        'expires_at' => $sub->expires_at->format('Y-m-d'),
                    ];
                }

                if (! $firstExpireDate || $sub->expires_at < $firstExpireDate) {
                    $firstExpireDate = $sub->expires_at;
                }
            }

            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar_url' => $user->avatar_url,
                'total_expected_monthly' => $totalMonthlyPrice,
                'first_expire_date' => $firstExpireDate ? $firstExpireDate->format('Y-m-d') : null,
                'services' => $services,
            ];
        });

        return Inertia::render('Admin/Plans/Index', [
            'users' => $users,
            'business_currency' => $businessCurrency,
        ]);
    }

    public function searchUsers(Request $request): JsonResponse
    {
        $search = $request->input('q');

        $users = User::with(['subscriptions' => function ($q) {
            $q->where('status', 'active')->where('expires_at', '>', now());
        }])
            ->when($search, function ($query, $search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->limit(20)
            ->get(['id', 'name', 'email'])
            ->map(function ($user) {
                $user->original_name = $user->name;
                $user->name = $user->name.' ('.$user->email.')';

                return $user;
            });

        return response()->json($users);
    }

    public function create(): Response
    {
        $items = $this->pricingService->getServiceItems();

        return Inertia::render('Admin/Plans/Create', [
            'services' => $items,
        ]);
    }

    public function store(StoreUserSubscriptionRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $user = User::findOrFail($validated['user_id']);

        $existingSub = $user->subscriptions()
            ->where('object', $validated['object'])
            ->where('status', 'active')
            ->first();

        $expiresAt = Carbon::parse($validated['expires_at']);

        if ($existingSub) {
            $existingSub->expires_at = $expiresAt;
            $existingSub->save();
        } else {
            UserSubscription::create([
                'user_id' => $user->id,
                'object' => $validated['object'],
                'status' => 'active',
                'started_at' => now(),
                'expires_at' => $expiresAt,
                'auto_renew' => false,
            ]);
        }

        return redirect()->route('admin.plans.index')->with('success', __('admin.subscription_assigned_successfully'));
    }
}
