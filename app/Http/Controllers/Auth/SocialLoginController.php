<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SocialLoginController extends Controller
{
    /**
     * Redirect the user to the Google authentication page.
     *
     * @return \Illuminate\Http\Response
     */
    public function redirect()
    {
        $clientId = config('services.google.client_id');
        $redirectUri = route('social.google.callback');
        
        if (!$clientId) {
            return redirect()->route('login')->with('error', 'Google Client ID is missing. Please configure it in services.');
        }

        $url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
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
     * @return \Illuminate\Http\Response
     */
    public function callback(Request $request)
    {
        try {
            if ($request->has('error')) {
                throw new \Exception($request->get('error'));
            }

            if (!$request->has('code')) {
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
                throw new \Exception('Failed to get token: ' . $response->body());
            }

            $tokenData = $response->json();
            
            // Get user info
            $userInfoResponse = Http::withToken($tokenData['access_token'])
                ->get('https://www.googleapis.com/oauth2/v3/userinfo');

            if ($userInfoResponse->failed()) {
                throw new \Exception('Failed to get user info: ' . $userInfoResponse->body());
            }

            $googleUser = $userInfoResponse->json();
            
            $user = User::where('email', $googleUser['email'])->first();
            if (!$user) {
                $user = User::create([
                    'name' => $googleUser['name'] ?? 'Google User',
                    'email' => $googleUser['email'],
                    'password' => bcrypt(Str::random(16)),
                    'email_verified_at' => now(), // Assume Google emails are verified
                ]);

                // Auto-assign currency based on GeoIP
                $detectedCountry = null;
                $geoDbPath = storage_path('app/geoip.mmdb');
                if (file_exists($geoDbPath)) {
                    try {
                        $reader = new \GeoIp2\Database\Reader($geoDbPath);
                        $ip = $request->ip();
                        if ($ip !== '127.0.0.1' && $ip !== '::1') {
                            $record = $reader->city($ip);
                            if ($record && $record->country->name) {
                                $detectedCountry = $record->country->name;
                            }
                        }
                    } catch (\Exception $e) {
                        // Ignore if IP not found in DB or DB missing
                    }
                }

                $mappedCurrencyCode = config('geo_currency.mapping.' . $detectedCountry, 'USD');
                $currency = \App\Models\Currency::where('currency', $mappedCurrencyCode)->first();

                if (!$currency) {
                    $currency = \App\Models\Currency::where('currency', 'USD')->first(); // fallback to default
                }

                if ($currency) {
                    $user->currency_id = $currency->id;
                    $user->save();
                }

                event(new \Illuminate\Auth\Events\Registered($user));
            }

            Auth::login($user, true);

            return redirect()->intended(route('dashboard', absolute: false));
            
        } catch (\Exception $e) {
            return redirect()->route('login')->with('error', __('general.google_login_failed', ['message' => $e->getMessage()]));
        }
    }
}
