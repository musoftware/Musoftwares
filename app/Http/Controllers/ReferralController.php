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
use App\Services\ReferralService;

class ReferralController extends Controller
{
    use \App\Traits\ConvertsCurrency;

    protected ReferralService $referralService;

    public function __construct(ReferralService $referralService)
    {
        $this->referralService = $referralService;
    }

    public function index(Request $request)
    {
        $user = Auth::user();

        // Auto-activate referral system and get/create primary link
        $referral = $this->referralService->ensureReferralSystemActive($user);

        return Inertia::render('Client/Dashboard/Referrals/Index', [
            'referral' => $referral,
            'commission_percentage' => $user->getAffiliateCommissionPercentage()
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

        return Inertia::render('Client/Dashboard/Referrals/Earns', [
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

        $commissionByUserId = $this->referralService->calculateCommissionByUserId($auth, $referred_users);

        return Inertia::render('Client/Dashboard/Referrals/Registers', [
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
            $referral = $this->referralService->processReferralRedirect($valueRef);
            if (!$referral) {
                return abort(404, __('messages.bad_referral'));
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

            $this->referralService->registerReferredUser(Auth::user(), $request->all());

            return redirect()->route('referrals.index')->with('success', __('messages.user_created_referral_success'));
        }

        return redirect()->route('referrals.index');
    }
}
