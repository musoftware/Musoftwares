<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\User;
use GeoIp2\Database\Reader;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

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
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

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
                // Non-blocking
            }
        }

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('onboarding.wizard');
    }
}
