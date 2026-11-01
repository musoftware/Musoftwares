<?php

namespace App\Http\Controllers\Auth;

use App\Enums\ConsentAction;
use App\Enums\ConsentType;
use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\User;
use App\Services\Privacy\ConsentAuditService;
use GeoIp2\Database\Reader;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Log;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'privacy_consent' => ['required', 'accepted'],
            'marketing_consent' => ['nullable', 'boolean'],
        ], [
            'privacy_consent.required' => __('privacy_compliance.register_privacy_required_error'),
            'privacy_consent.accepted' => __('privacy_compliance.register_privacy_required_error'),
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        /** @var ConsentAuditService $consentService */
        $consentService = app(ConsentAuditService::class);
        $policyVersion = $consentService->currentPolicyVersion();
        $consentService->record($user, ConsentType::PrivacyPolicy, ConsentAction::Granted, $request, $policyVersion);
        $consentService->record($user, ConsentType::CrossBorderTransfer, ConsentAction::Granted, $request, $policyVersion);

        if ($request->boolean('marketing_consent')) {
            $consentService->record($user, ConsentType::DirectMarketingEmail, ConsentAction::Granted, $request, $policyVersion);
            $consentService->record($user, ConsentType::DirectMarketingSms, ConsentAction::Granted, $request, $policyVersion);
        }

        Cookie::queue(Cookie::make(ConsentAuditService::ESSENTIAL_COOKIE_NAME, $policyVersion, 60 * 24 * 365));

        // Auto-assign currency based on GeoIP & DB country mappings
        /** @var \App\Services\IpGeolocationService $geoService */
        $geoService = app(\App\Services\IpGeolocationService::class);
        $currency = $geoService->getCurrencyForIp($request->ip()) ?? \App\Models\Currency::getDefault();

        if ($currency) {
            $user->currency_id = $currency->id;
            $user->save();
        }

        // Attach referral if visitor arrived via referral campaign link
        $refKey = $request->session()->get('referral');
        if ($refKey) {
            try {
                $referral = \App\Models\UserReferral::resolveRef($refKey);
                if ($referral && $referral->user_id && $referral->user_id !== $user->id) {
                    $user->ref_user_id = $referral->user_id;
                    $user->save();

                    $referrer = \App\Models\User::find($referral->user_id);
                    if ($referrer) {
                        app(\App\Services\LoyaltyService::class)->awardPointsForEvent(
                            user: $referrer,
                            eventType: 'referral_registered',
                            reference: $user,
                            context: [
                                'referred_user_id'   => $user->id,
                                'referred_user_name' => $user->name,
                                'channel'            => 'referral_link',
                            ]
                        );
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Registration: referral loyalty award failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            }
        }

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('onboarding.wizard');
    }
}
