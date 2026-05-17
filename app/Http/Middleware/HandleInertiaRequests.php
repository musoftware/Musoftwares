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
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
                'is_impersonating' => session()->has('impersonator_id'),
            ],
            'notifications' => function () use ($request) {
                if ($request->user()) {
                    return [
                        'unread_count' => $request->user()->unreadNotifications()->count(),
                        'recent' => $request->user()->unreadNotifications()->take(5)->get(),
                    ];
                }
                return null;
            },
            'wallet' => function () use ($request) {
                if ($request->user() && \Illuminate\Support\Facades\Schema::hasTable('wallets')) {
                    $wallet = $request->user()->getWallet();
                    return [
                        'id' => $wallet->id,
                        'balance' => $wallet->balance,
                        'earned_balance' => $wallet->earned_balance ?? 0,
                        'currency' => $wallet->currency ?? 'USD',
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
