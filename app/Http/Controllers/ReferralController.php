<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserReferral;
use App\Services\BalanceService;
use App\Services\ReferralService;
use App\Traits\ConvertsCurrency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ReferralController extends Controller
{
    use ConvertsCurrency;

    protected ReferralService $referralService;

    protected BalanceService $balanceService;

    public function __construct(ReferralService $referralService, BalanceService $balanceService)
    {
        $this->referralService = $referralService;
        $this->balanceService = $balanceService;
    }

    public function index(Request $request)
    {
        $user = Auth::user();

        // Auto-activate referral system and get/create primary link.
        // Note: ensureReferralSystemActive mutates the user's allow_referral_system
        // flag and creates a UserReferral row on read. If that side-effect is
        // undesirable, move it behind a POST activation endpoint instead.
        $referral = $this->referralService->ensureReferralSystemActive($user);

        $embedKey = \App\Models\UserEmbedKey::where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        return Inertia::render('Client/Dashboard/Referrals/Index', [
            'referral' => $referral,
            'commission_percentage' => $user->getAffiliateCommissionPercentage(),
            'embedKey' => $embedKey,
        ]);
    }

    public function earns(Request $request)
    {
        $user = Auth::user();

        // pending_commission = earned but not yet moved to user_balance.
        // available_commission = pending_commission minus any in-flight withdrawals.
        // The legacy calculation (user_balance - withdrawing_commission) was wrong
        // because user_balance mixes deposits with earned funds and would inflate
        // the visible commission to anyone with a positive deposit balance.
        $pending_balance = (float) ($user->pending_commission ?? 0);
        $pending_balance_str = $this->formatAmount($pending_balance, $user->currency_id ?: 1);

        $available_commission = $this->balanceService->availableEarnedBalance($user);
        $available_commission_str = $this->formatAmount($available_commission, $user->currency_id ?: 1);

        $withdrawed_commission = (float) ($user->withdrawn_commission ?? 0);
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

            // A user has exactly one primary referral campaign. Multiple
            // campaigns per user created ambiguity in the dashboard which
            // assumed a single primary link.
            $existing = UserReferral::where('user_id', Auth::id())->first();
            if ($existing) {
                return redirect()->route('referrals.index')->with('info', __('messages.referral_already_exists'));
            }

            $client_request = new UserReferral;
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
        if (! $referral) {
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
                Rule::unique('user_referrals', 'slug')
                    ->ignore($referral->id)
                    ->whereNull('deleted_at'),
            ],
        ], [
            'slug.regex' => __('messages.referral_slug_regex_hint'),
            'slug.unique' => __('messages.referral_slug_taken'),
        ]);

        $referral->slug = $slugNormalized;
        $referral->save();

        return redirect()->route('referrals.index')->with('success', $referral->slug
            ? __('messages.referral_short_link_ready', ['url' => url('r/'.$referral->slug)])
            : __('messages.referral_slug_removed_default'));
    }

    public function registers()
    {
        $auth = Auth::user();

        if (method_exists($auth, 'my_ref_users')) {
            $referred_users = $auth->my_ref_users()
                ->whereNotNull('email_verified_at')
                ->orderBy('created_at', 'desc')
                ->paginate(14);
        } else {
            $referred_users = User::where('ref_user_id', $auth->id)
                ->whereNotNull('email_verified_at')
                ->orderBy('created_at', 'desc')
                ->paginate(14);
        }

        $commissionByUserId = $this->referralService->calculateCommissionByUserId($auth, $referred_users);

        // Global aggregate over *all* referred users, not just the current page.
        // Without this, the dashboard table per-user totals look incomplete on
        // page 2+ and have historically misled referrers into thinking their
        // commissions were lost.
        $globalTotal = $this->referralService->calculateTotalCommissionForReferrer($auth);

        return Inertia::render('Client/Dashboard/Referrals/Registers', [
            'referred_users' => $referred_users,
            'commissionByUserId' => $commissionByUserId,
            'global_commission_total' => $globalTotal,
        ]);
    }

    public function referral_redirect(Request $request, $ref = null)
    {
        if ($ref != null) {
            $request->session()->put('referral', $ref);
        }
        $HasRef = $request->session()->get('referral') != null;
        $valueRef = $request->session()->get('referral');

        if ($HasRef) {
            $referral = $this->referralService->processReferralRedirect($valueRef, $request);
            if (! $referral) {
                return abort(404, __('messages.bad_referral'));
            }
        }

        $to = $request->query('to');
        if ($to !== null && $to !== '') {
            $to = ltrim($to, '/');

            // Restrict ?to= to local relative paths. The previous regex allowed
            // payloads like `//evil.example.com` after ltrim('/') which became
            // `/evil.example.com` and triggered an open redirect.
            if (preg_match('#^[a-z0-9][a-z0-9\-/_]*$#i', $to) && ! str_starts_with($to, '//')) {
                return redirect()->to('/'.$to);
            }
        }

        return redirect()->route('register');
    }

    public function activate_ref(Request $request)
    {
        RateLimiter::attempt(
            'activate_ref:'.Auth::id(),
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
        if (Auth::user()->allow_referral_system != '1') {
            return redirect()->route('referrals.index');
        }

        // Rate-limit referrer-driven user creation so this surface can't be
        // abused to mass-register disposable accounts.
        $executed = RateLimiter::attempt(
            'store_user_ref:'.Auth::id(),
            $perMinute = 5,
            function () {
                return true;
            }
        );

        if (! $executed) {
            return redirect()->route('referrals.index')
                ->with('error', __('messages.too_many_referral_user_creation_attempts'));
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255', function ($attribute, $value, $fail) {
                if (! Str::contains($value, ' ')) {
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

    public function generate_embed_key(Request $request)
    {
        $user = Auth::user();

        $existing = \App\Models\UserEmbedKey::where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        if ($existing) {
            return redirect()->route('referrals.index')->with('info', __('general.embed_key_already_exists'));
        }

        $key = bin2hex(random_bytes(32));

        \App\Models\UserEmbedKey::create([
            'user_id' => $user->id,
            'key' => $key,
            'is_active' => true,
        ]);

        return redirect()->route('referrals.index')->with('success', __('general.embed_key_generated_success'));
    }
}
