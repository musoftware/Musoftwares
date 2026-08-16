<?php

namespace App\Http\Middleware;

use App\Models\AdminSettings;
use App\Models\Currency;
use App\Models\RecurringNotice;
use App\Models\WebsiteService;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Middleware;
use Modules\CRM\app\Core\FeatureManager;
use Modules\CRM\app\Core\LimitManager;

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

        if (auth('crm_team')->check()) {
            $crmMember = auth('crm_team')->user();
            $user = $crmMember?->workspace?->owner ?? $user;
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar_url' => $user->avatar_url,
                    'user_balance' => $user->user_balance,
                    'currency_id' => $user->currency_id,
                    'onboarding_completed' => (bool)$user->onboarding_completed,
                    'role' => strtolower($user->roles->first()->name ?? $user->role ?? 'user'),
                    'roles' => $user->roles->pluck('name')->map(fn ($r) => strtolower($r))->toArray(),
                    'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
                    'is_admin' => method_exists($user, 'isAdmin') ? $user->isAdmin() : false,
                    'workspace_settings' => $user->workspace_settings ?? [],
                    'enable_3d_dashboard' => (bool)$user->enable_3d_dashboard,
                    'openai_api_key' => $user->openai_api_key,
                    'openai_model' => $user->openai_model,
                    'gemini_api' => $user->gemini_api,
                    'gemini_model' => $user->gemini_model,
                    'default_ai_model' => $user->default_ai_model,
                ] : null,
                'team_member' => null,
                'crm_team_member' => Auth::guard('crm_team')->user(),
                'is_impersonating' => session()->has('impersonator_id'),
                'has_ios_shortcut_active' => $user ? $user->last_shortcut_sync_at !== null : false,
                'active_modules' => function () use ($user) {
                    if (! $user) {
                        return [];
                    }
                    try {
                        $service = app(SubscriptionService::class);

                        return [
                            'erp' => $service->hasActiveSubscription($user, 'erp'),
                            'booking' => $service->hasActiveSubscription($user, 'booking'),
                            'intelligence' => $service->hasActiveSubscription($user, 'intelligence'),
                            'tools' => $service->hasActiveSubscription($user, 'tools'),
                            'crm' => $service->hasActiveSubscription($user, 'crm'),
                            'marketplace' => true,
                        ];
                    } catch (\Throwable $e) {
                        return [
                            'erp' => true,
                            'booking' => true,
                            'intelligence' => true,
                            'tools' => true,
                            'crm' => true,
                            'marketplace' => true,
                        ];
                    }
                },
                'crm_features' => function () use ($user) {
                    if (! $user) {
                        return [];
                    }
                    if (class_exists(FeatureManager::class)) {
                        return app(FeatureManager::class)->getAllForUser($user);
                    }

                    return [];
                },
                'crm_limits' => function () {
                    if (class_exists(LimitManager::class)) {
                        return app(LimitManager::class)->getAllLimits();
                    }

                    return [];
                },
                'erp_addons' => function () use ($user) {
                    if (! $user) {
                        return [];
                    }
                    try {
                        $erpAddons = collect(config('saas.addons', []))
                            ->filter(fn ($a) => ($a['parent'] ?? '') === 'erp')
                            ->keys();

                        return $erpAddons->filter(fn ($slug) => $user->hasModuleSubscription($slug))->values()->toArray();
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
                        'currency' => $user->currency_id ? (Currency::find($user->currency_id)?->currency) : null,
                    ];
                }

                return null;
            },
            'tenant' => function () {
                return null;
            },
            'settings' => [
                'base_currency' => function () {
                    if (class_exists(AdminSettings::class)) {
                        return AdminSettings::business_currency_name();
                    }

                    return 'USD';
                },
                'business_name' => function () {
                    return class_exists(AdminSettings::class) ? AdminSettings::GetValue('business_name', 'musoftware') : 'musoftware';
                },
                'business_phone' => function () {
                    return class_exists(AdminSettings::class) ? AdminSettings::GetValue('business_phone', '+20 101 521 8548') : '+20 101 521 8548';
                },
                'business_address' => function () {
                    return class_exists(AdminSettings::class) ? AdminSettings::GetValue('business_address', 'Suez, Egypt') : 'Suez, Egypt';
                },
                'business_email' => function () {
                    return class_exists(AdminSettings::class) ? AdminSettings::GetValue('business_email', 'admin@musoftwares.com') : 'admin@musoftwares.com';
                },
            ],
            'site_info' => [
                'brand' => 'Musoftwares',
                'description' => 'Bespoke software architecture, automation pipelines, and custom web applications.',
                'contact_email' => 'admin@musoftwares.com',
            ],
            'active_currency' => function () use ($request, $user) {
                if ($user && $user->currency_id) {
                    $c = Currency::find($user->currency_id);
                    if ($c) {
                        return [
                            'id' => $c->id,
                            'currency' => $c->currency,
                            'symbol' => $c->symbol,
                            'string_format' => $c->string_format,
                            'is_default' => (bool) $c->is_default,
                        ];
                    }
                }

                $guestCurrencyId = session('guest_currency_id');
                if ($guestCurrencyId) {
                    $c = Currency::find($guestCurrencyId);
                    if ($c) {
                        return [
                            'id' => $c->id,
                            'currency' => $c->currency,
                            'symbol' => $c->symbol,
                            'string_format' => $c->string_format,
                            'is_default' => (bool) $c->is_default,
                        ];
                    }
                }

                // Resolve via IpGeolocationService and cache by IP to prevent external HTTP delays
                $ip = $request->ip();
                $cId = \Illuminate\Support\Facades\Cache::remember('ip_curr_id_' . md5((string)$ip), 86400, function () use ($request) {
                    /** @var \App\Services\IpGeolocationService $geoService */
                    $geoService = app(\App\Services\IpGeolocationService::class);
                    return $geoService->getCurrencyForIp($request->ip())?->id ?? Currency::getDefault()?->id;
                });
                $c = $cId ? Currency::find($cId) : Currency::getDefault();

                if ($c) {
                    session(['guest_currency_id' => $c->id]);
                    return [
                        'id' => $c->id,
                        'currency' => $c->currency,
                        'symbol' => $c->symbol,
                        'string_format' => $c->string_format,
                        'is_default' => (bool) $c->is_default,
                    ];
                }

                return [
                    'id' => 1,
                    'currency' => 'USD',
                    'symbol' => '$',
                    'string_format' => '$%01.2f',
                    'is_default' => true,
                ];
            },
            'currencies' => fn () => \Illuminate\Support\Facades\Cache::remember('global_currencies_list', 86400, fn () => Currency::all()->map(fn ($c) => [
                'id' => $c->id,
                'currency' => $c->currency,
                'symbol' => $c->symbol,
                'string_format' => $c->string_format,
                'country_codes' => $c->country_codes ?? [],
                'is_default' => (bool) $c->is_default,
            ])->toArray()),
            'website_services' => fn () => \Illuminate\Support\Facades\Cache::remember('global_website_services', 86400, fn () => class_exists(WebsiteService::class) ? WebsiteService::all()->toArray() : []),
            'flash' => [
                'message' => fn () => $request->session()->get('message'),
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'danger' => fn () => $request->session()->get('danger'),
                'warning' => fn () => $request->session()->get('warning'),
                'info' => fn () => $request->session()->get('info'),
                'new_publishable_key' => fn () => $request->session()->get('new_publishable_key'),
                'new_secret_key' => fn () => $request->session()->get('new_secret_key'),
                'ios_shortcut_token' => fn () => $request->session()->get('ios_shortcut_token'),
            ],
            'locale' => app()->getLocale(),
            'recurring_notices_today' => function () use ($user) {
                if (! $user || ! class_exists(RecurringNotice::class)) {
                    return [];
                }
                try {
                    return RecurringNotice::dueToday()
                        ->map(fn ($notice) => [
                            'id' => $notice->id,
                            'title' => $notice->title,
                            'message' => $notice->message,
                            'type' => $notice->type,
                        ])
                        ->toArray();
                } catch (\Throwable $e) {
                    return [];
                }
            },
        ];
    }
}
