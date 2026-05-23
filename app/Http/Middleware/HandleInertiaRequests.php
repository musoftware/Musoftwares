<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        if (auth('erp_team')->check()) {
            $user = auth('erp_team')->user()?->tenant?->user;
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
                'team_member' => \Illuminate\Support\Facades\Auth::guard('erp_team')->user(),
                'is_impersonating' => session()->has('impersonator_id'),
                'active_modules' => function () use ($user) {
                    if (!$user) return [];
                    try {
                        $service = app(\App\Services\SubscriptionService::class);
                        return [
                            'erp' => $service->hasActiveSubscription($user, 'erp'),
                            'freelance' => $service->hasActiveSubscription($user, 'freelance'),
                            'booking' => $service->hasActiveSubscription($user, 'booking'),
                            'intelligence' => $service->hasActiveSubscription($user, 'intelligence'),
                            'tools' => $service->hasActiveSubscription($user, 'tools'),
                            'marketplace' => true,
                        ];
                    } catch (\Throwable $e) {
                        return [
                            'erp' => true, 
                            'freelance' => true, 
                            'booking' => true,
                            'intelligence' => true,
                            'tools' => true,
                            'marketplace' => true
                        ];
                    }
                }
            ],
            'notifications' => function () use ($user) {
                if ($user) {
                    return [
                        'unread_count' => $user->unreadNotifications()->count(),
                        'recent' => $user->unreadNotifications()->take(5)->get(),
                    ];
                }
                return null;
            },
            'wallet' => function () use ($user) {
                if ($user) {
                    return [
                        'id' => null,
                        'balance' => $user->user_balance,
                        'earned_balance' => 0,
                        'currency' => $user->preferred_currency ?? 'USD',
                    ];
                }
                return null;
            },
            'settings' => [
                'base_currency' => 'USD'
            ],
            'flash' => [
                'message' => fn () => $request->session()->get('message')
            ],
        ];
    }
}
