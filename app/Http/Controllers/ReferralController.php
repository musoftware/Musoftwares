<?php

namespace App\Http\Controllers;

use App\Helpers\FinanceHelper;
use App\Models\UserReferral;
use App\Models\Earning;
use App\Models\CurrenciesExchange;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ReferralController extends Controller
{
    use \App\Traits\ConvertsCurrency;

    public function index(Request $request)
    {
        $user = Auth::user();

        // Auto-activate referral system if not active
        if ($user->allow_referral_system != '1') {
            $user->allow_referral_system = '1';
            $user->save();
        }

        // Auto-create a primary referral link if none exists
        $referral = UserReferral::where('user_id', $user->id)->first();
        
        if (!$referral) {
            $referral = new UserReferral();
            $referral->user_id = $user->id;
            $referral->title = 'Primary Campaign';
            $referral->key = sha1(md5(uniqid() . $user->id));
            $referral->save();
        }

        return Inertia::render('Dashboard/Referrals/Index', [
            'referral' => $referral
        ]);
    }

    public function earns(Request $request)
    {
        $user = Auth::user();
        
        // Use the exact database columns that are synced by BalancesHelper
        $pending_balance = $user->pending_commission;
        $pending_balance_str = $this->formatAmount($pending_balance, $user->currency_id ?: 1);

        // Available commission in legacy was: user_balance - withdrawing_commission
        $available_commission = max(0, (float)($user->user_balance - $user->withdrawing_commission));
        $available_commission_str = $this->formatAmount($available_commission, $user->currency_id ?: 1);

        $withdrawed_commission = $user->withdrawn_commission;
        $withdrawed_commission_str = $this->formatAmount($withdrawed_commission, $user->currency_id ?: 1);

        return Inertia::render('Dashboard/Referrals/Earns', [
            'pending_balance' => $pending_balance,
            'pending_balance_str' => $pending_balance_str,
            'available_commission' => $available_commission,
            'available_commission_str' => $available_commission_str,
            'withdrawed_commission' => $withdrawed_commission,
            'withdrawed_commission_str' => $withdrawed_commission_str,
        ]);
    }

    public function store_referral(Request $request)
    {
        if (Auth::user()->allow_referral_system == '1') {
            $request->validate([
                'title' => ['required', 'max:255'],
            ]);

            $client_request = new UserReferral();
            $client_request->user_id = Auth::id();
            $client_request->title = request('title');
            $client_request->key = sha1(md5(uniqid()));
            $client_request->save();

            return redirect()->route('referrals.index')->with('success', __('messages.referral_created_success'));
        }

        return redirect()->route('referrals.index');
    }

    public function update_slug(Request $request)
    {
        $user = Auth::user();
        $referral = UserReferral::where('user_id', $user->id)->first();
        if (!$referral) {
            return redirect()->route('referrals.index')->with('error', __('messages.referral_not_found'));
        }

        // Once slug is set, it cannot be changed
        if ($referral->slug !== null && trim($referral->slug) !== '') {
            return redirect()->route('referrals.index')->with('info', __('messages.referral_slug_set_cannot_change'));
        }

        $slugRaw = $request->input('slug');
        $slugNormalized = ($slugRaw === null || trim((string) $slugRaw) === '') ? null : strtolower(trim($slugRaw));

        $request->merge(['slug' => $slugNormalized]);
        $request->validate([
            'slug' => [
                'nullable',
                'string',
                'max:100',
                'min:2',
                'regex:/^[a-z0-9\-]+$/',
                Rule::unique('user_referrals', 'slug')->ignore($referral->id),
            ],
        ], [
            'slug.regex' => __('messages.referral_slug_regex_hint'),
            'slug.unique' => __('messages.referral_slug_taken'),
        ]);

        $referral->slug = $slugNormalized;
        $referral->save();

        return redirect()->route('referrals.index')->with('success', $referral->slug
            ? __('messages.referral_short_link_ready', ['url' => url('r/' . $referral->slug)])
            : __('messages.referral_slug_removed_default'));
    }

    public function registers()
    {
        $auth = Auth::user();
        
        // This requires a my_ref_users() relationship on the User model
        if (method_exists($auth, 'my_ref_users')) {
            $referred_users = $auth->my_ref_users()
                ->orderBy('created_at', 'desc')
                ->paginate(14);
        } else {
            // Fallback assuming users table has ref_user_id
            $referred_users = \App\Models\User::where('ref_user_id', $auth->id)
                ->orderBy('created_at', 'desc')
                ->paginate(14);
        }

        $ids = $referred_users->pluck('id');
        $earnings = Earning::query()
            ->where('user_id', $auth->id)
            ->whereIn('referred_user_id', $ids)
            ->get();

        $commissionByUserId = [];
        foreach ($earnings->groupBy('referred_user_id') as $referred_user_id => $userEarnings) {
            $total = 0;
            foreach ($userEarnings as $e) {
                // Ensure CurrenciesExchange exists or fallback
                if (class_exists(CurrenciesExchange::class)) {
                    $total += CurrenciesExchange::RateToday($e->amount, $e->currency, $auth->currency);
                    // RateToday expects currency IDs in the legacy system.
                } else {
                    $total += $e->amount;
                }
            }
            $commissionByUserId[$referred_user_id] = round($total, 2);
        }

        return Inertia::render('Dashboard/Referrals/Registers', [
            'referred_users' => $referred_users,
            'commissionByUserId' => $commissionByUserId
        ]);
    }

    public function reward()
    {
        RateLimiter::attempt(
            'ref_reward:' . Auth::user()->id,
            $perMinute = 1,
            function () {
                // In the legacy system, EarnPerRegister::regEquivalent() simply returned 0.
                // We keep the exact behavior of blocking/doing nothing if 0.
                $amount = 0; 

                if ($amount > 0) {
                    // Logic would go here if EarnPerRegister was fully ported
                }
            }
        );

        return redirect(route('referrals.registers'));
    }

    public function referral_redirect(Request $request, $ref = null)
    {
        if ($ref != null) {
            $request->session()->put('referral', $ref);
        }
        $HasRef = $request->session()->get('referral') != null;
        $valueRef = $request->session()->get('referral');

        if ($HasRef) {
            $referral = UserReferral::resolveRef($valueRef);
            if (!$referral) {
                return abort(404, __('messages.bad_referral'));
            }
            if (str_contains($_SERVER['HTTP_USER_AGENT'] ?? '', 'FBAV')) {
                header('X-Frame-Options: DENY');
                header('Referrer-Policy: origin');
            }
            if (empty($_SERVER['HTTP_USER_AGENT']) || !preg_match('~(bot|crawl)~i', $_SERVER['HTTP_USER_AGENT'])) {
                UserReferral::IncViewRef($valueRef);
            }
        }

        $to = $request->query('to');
        if ($to !== null && $to !== '') {
            $to = ltrim($to, '/');
            if (!preg_match('#^https?://#i', $to) && preg_match('#^[a-z0-9\-/_]+$#i', $to)) {
                return redirect()->to('/' . $to);
            }
        }

        return redirect()->route('register');
    }

    public function activate_ref(Request $request)
    {
        RateLimiter::attempt(
            'activate_ref:' . Auth::id(),
            $perMinute = 1,
            function () {
                $usr = Auth::user();
                $usr->allow_referral_system = 1;
                $usr->save();
            }
        );

        return redirect()->route('referrals.index')->with('success', __('messages.referral_system_activated'));
    }

    public function store_user(Request $request)
    {
        if (Auth::user()->allow_referral_system == '1') {
            $request->validate([
                'name' => ['required', 'string', 'max:255', function ($attribute, $value, $fail) {
                    if (!\Illuminate\Support\Str::contains($value, ' ')) {
                        $fail(__('messages.name_must_include_last_name'));
                    }
                }],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
                'phone_number' => ['nullable', 'string', 'max:20'],
                'affiliate_commission_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            ]);

            $user = \App\Models\User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => \Illuminate\Support\Facades\Hash::make($request->password),
                'phone_number' => $request->phone_number,
                'currency' => Auth::user()->currency_name(),
                'affiliate_commission_percentage' => $request->affiliate_commission_percentage ?? 1.00,
                'add_commission_to_total' => $request->has('affiliate_commission_percentage'),
            ]);

            $user->ref_user_id = Auth::id();
            $user->save();

            event(new \Illuminate\Auth\Events\Registered($user));

            return redirect()->route('referrals.index')->with('success', __('messages.user_created_referral_success'));
        }

        return redirect()->route('referrals.index');
    }
}
