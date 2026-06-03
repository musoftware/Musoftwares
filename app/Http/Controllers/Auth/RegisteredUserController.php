<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
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

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('onboarding.wizard');
    }
}
