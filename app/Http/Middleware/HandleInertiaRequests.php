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
        if (auth('crm_team')->check()) {
            $crmMember = auth('crm_team')->user();
            $user = $crmMember?->workspace?->owner ?? $user;
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? array_merge($user->toArray(), [
                    'role' => strtolower($user->roles->first()->name ?? 'user'),
                    'roles' => $user->roles->pluck('name')->map(fn($r) => strtolower($r))->toArray(),
                ]) : null,
                'team_member' => \Illuminate\Support\Facades\Auth::guard('erp_team')->user(),
                'crm_team_member' => \Illuminate\Support\Facades\Auth::guard('crm_team')->user(),
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
                            'crm' => $service->hasActiveSubscription($user, 'crm'),
                            'marketplace' => true,
                        ];
                    } catch (\Throwable $e) {
                        return [
                            'erp' => true, 
                            'freelance' => true, 
                            'booking' => true,
                            'intelligence' => true,
                            'tools' => true,
                            'crm' => true,
                            'marketplace' => true
                        ];
                    }
                },
                'crm_features' => function () use ($user) {
                    if (!$user) return [];
                    if (class_exists(\Modules\CRM\app\Core\FeatureManager::class)) {
                        return app(\Modules\CRM\app\Core\FeatureManager::class)->getAllForUser($user);
                    }
                    return [];
                },
                'crm_limits' => function () {
                    if (class_exists(\Modules\CRM\app\Core\LimitManager::class)) {
                        return app(\Modules\CRM\app\Core\LimitManager::class)->getAllLimits();
                    }
                    return [];
                },
                'erp_addons' => function () use ($user) {
                    if (!$user) return [];
                    try {
                        $erpAddons = collect(config('saas.addons', []))
                            ->filter(fn($a) => ($a['parent'] ?? '') === 'erp')
                            ->keys();
                        return $erpAddons->filter(fn($slug) => $user->hasModuleSubscription($slug))->values()->toArray();
                    } catch (\Throwable $e) {
                        return [];
                    }
                },
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
            'tenant' => function () use ($user) {
                if (!$user) return null;
                if (auth('erp_team')->check()) {
                    return auth('erp_team')->user()?->tenant;
                }
                if (class_exists(\Modules\ERP\Models\Tenant::class)) {
                    return \Modules\ERP\Models\Tenant::where('user_id', $user->id)->first();
                }
                return null;
            },
            'settings' => [
                'base_currency' => function () use ($user) {
                    if ($user && class_exists(\Modules\ERP\Models\Tenant::class)) {
                        $tenant = null;
                        if (auth('erp_team')->check()) {
                            $tenant = auth('erp_team')->user()?->tenant;
                        } else {
                            $tenant = \Modules\ERP\Models\Tenant::where('user_id', $user->id)->first();
                        }
                        if ($tenant && $tenant->base_currency_id) {
                            $baseCurrency = \App\Models\Currency::find($tenant->base_currency_id);
                            if ($baseCurrency) {
                                return $baseCurrency->currency;
                            }
                        }
                    }
                    if (class_exists(\App\Models\AdminSettings::class)) {
                        return \App\Models\AdminSettings::business_currency_name();
                    }
                    return 'USD';
                }
            ],
            'currencies' => \App\Models\Currency::all()->map(fn($c) => [
                'id' => $c->id,
                'currency' => $c->currency,
                'symbol' => $c->symbol,
                'string_format' => $c->string_format,
            ])->toArray(),
            'flash' => [
                'message' => fn () => $request->session()->get('message'),
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'danger' => fn () => $request->session()->get('danger'),
                'warning' => fn () => $request->session()->get('warning'),
                'new_publishable_key' => fn () => $request->session()->get('new_publishable_key'),
                'new_secret_key' => fn () => $request->session()->get('new_secret_key'),
            ],
            'is_lance_domain' => $request->getHost() === 'lance.musoftwares.com',
        ];
    }
}
