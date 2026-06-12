<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialLoginController extends Controller
{
    /**
     * Redirect the user to the Google authentication page.
     *
     * @return \Illuminate\Http\Response
     */
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Obtain the user information from Google.
     *
     * @return \Illuminate\Http\Response
     */
    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
            
            // Find existing user by email, or create a new one with a random password
            $user = User::firstOrCreate(
                ['email' => $googleUser->getEmail()],
                [
                    'name' => $googleUser->getName() ?? 'Google User',
                    'password' => bcrypt(Str::random(16)),
                    'email_verified_at' => now(), // Assume Google emails are verified
                ]
            );

            Auth::login($user, true);

            return redirect()->intended(route('dashboard', absolute: false));
            
        } catch (\Exception $e) {
            return redirect()->route('login')->with('error', __('general.google_login_failed', ['message' => $e->getMessage()]));
        }
    }
}
