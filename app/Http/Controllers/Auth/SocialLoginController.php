<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\User;
use App\Models\UserEmail;
use GeoIp2\Database\Reader;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SocialLoginController extends Controller
{
    /**
     * Redirect the user to the Google authentication page.
     *
     * @return Response
     */
    public function redirect()
    {
        $clientId = config('services.google.client_id');
        $redirectUri = route('social.google.callback');

        if (! $clientId) {
            return redirect()->route('login')->with('error', 'Google Client ID is missing. Please configure it in services.');
        }

        $url = 'https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'openid profile email',
            'access_type' => 'online',
            'prompt' => 'select_account',
        ]);

        return redirect()->away($url);
    }

    /**
     * Obtain the user information from Google.
     *
     * @return Response
     */
    public function callback(Request $request)
    {
        try {
            if ($request->has('error')) {
                throw new \Exception($request->get('error'));
            }

            if (! $request->has('code')) {
                throw new \Exception('No authorization code provided.');
            }

            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'redirect_uri' => route('social.google.callback'),
                'grant_type' => 'authorization_code',
                'code' => $request->get('code'),
            ]);

            if ($response->failed()) {
                throw new \Exception('Failed to get token: '.$response->body());
            }

            $tokenData = $response->json();

            // Get user info
            $userInfoResponse = Http::withToken($tokenData['access_token'])
                ->get('https://www.googleapis.com/oauth2/v3/userinfo');

            if ($userInfoResponse->failed()) {
                throw new \Exception('Failed to get user info: '.$userInfoResponse->body());
            }

            $googleUser = $userInfoResponse->json();
            $googleEmail = strtolower(trim($googleUser['email'] ?? ''));

            if (empty($googleEmail)) {
                throw new \Exception('Google account did not return an email address.');
            }

            // Look up existing user across primary email and secondary emails
            $existingUser = User::findForLogin($googleEmail);

            // Handle scenario when user is already logged in (Linking Google Account)
            if (Auth::check()) {
                $authUser = Auth::user();

                if ($existingUser) {
                    if ($existingUser->id === $authUser->id) {
                        return redirect()->route('profile.edit')->with('status', __('general.google_account_already_linked', ['email' => $googleEmail]));
                    }

                    return redirect()->route('profile.edit')->with('error', __('general.google_email_linked_other_account', ['email' => $googleEmail]));
                }

                // Attach Google email as a verified secondary email to the current authenticated user
                UserEmail::create([
                    'user_id' => $authUser->id,
                    'email' => $googleEmail,
                    'verified_at' => now(),
                    'source' => UserEmail::SOURCE_SELF,
                    'added_by_user_id' => $authUser->id,
                ]);

                return redirect()->route('profile.edit')->with('status', __('general.google_account_linked_success', ['email' => $googleEmail]));
            }

            // Handle Guest Login / Registration
            $user = $existingUser;

            if (! $user) {
                $user = User::create([
                    'name' => $googleUser['name'] ?? 'Google User',
                    'email' => $googleEmail,
                    'password' => bcrypt(Str::random(16)),
                    'email_verified_at' => now(), // Assume Google emails are verified
                ]);

                // Auto-assign currency based on GeoIP & DB country mappings
                /** @var \App\Services\IpGeolocationService $geoService */
                $geoService = app(\App\Services\IpGeolocationService::class);
                $currency = $geoService->getCurrencyForIp($request->ip());

                if ($currency) {
                    $user->currency_id = $currency->id;
                    $user->save();
                }

                event(new Registered($user));
            }

            Auth::login($user, true);

            return redirect()->intended(route('dashboard', absolute: false));

        } catch (\Exception $e) {
            $targetRoute = Auth::check() ? 'profile.edit' : 'login';
            return redirect()->route($targetRoute)->with('error', __('general.google_login_failed', ['message' => $e->getMessage()]));
        }
    }
}
