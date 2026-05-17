<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Redirect;

class OnboardingController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();

        if ($user->onboarding_completed) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        $currencies = [
            ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$'],
            ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€'],
            ['code' => 'GBP', 'name' => 'British Pound', 'symbol' => '£'],
            ['code' => 'EGP', 'name' => 'Egyptian Pound', 'symbol' => 'EGP'],
            ['code' => 'SAR', 'name' => 'Saudi Riyal', 'symbol' => 'SAR'],
            ['code' => 'AED', 'name' => 'UAE Dirham', 'symbol' => 'AED'],
            ['code' => 'CAD', 'name' => 'Canadian Dollar', 'symbol' => 'CA$'],
            ['code' => 'AUD', 'name' => 'Australian Dollar', 'symbol' => 'AU$'],
            ['code' => 'JPY', 'name' => 'Japanese Yen', 'symbol' => '¥'],
        ];

        $countries = [
            'United States', 'United Kingdom', 'Canada', 'Australia', 'Germany',
            'France', 'Egypt', 'Saudi Arabia', 'United Arab Emirates', 'Japan',
            'Singapore', 'Netherlands', 'Switzerland', 'Sweden', 'Spain', 'Italy', 'Brazil', 'India'
        ];

        return Inertia::render('Auth/OnboardingWizard', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'country' => $user->country ?: '',
                'city' => $user->city ?: '',
                'mobile_1' => $user->mobile_1 ?: '',
                'mobile_2' => $user->mobile_2 ?: '',
                'telegram_username' => $user->telegram_username ?: '',
                'whatsapp_number' => $user->whatsapp_number ?: '',
                'preferred_currency' => $user->preferred_currency ?: '',
                'preferred_currency_locked_at' => $user->preferred_currency_locked_at ? $user->preferred_currency_locked_at->toISOString() : null,
            ],
            'currencies' => $currencies,
            'countries' => $countries,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $isCurrencyLocked = $user->preferred_currency_locked_at !== null;

        $action = $request->input('action', 'autosave');
        $step = (int) $request->input('step', 1);

        $rules = [];

        if ($step === 1 || $action === 'complete') {
            $rules['country'] = ['required', 'string', 'max:255'];
            $rules['city'] = ['required', 'string', 'max:255'];
        }

        if ($step === 2 || $action === 'complete') {
            $rules['mobile_1'] = ['required', 'string', 'max:50'];
            $rules['mobile_2'] = ['nullable', 'string', 'max:50'];
            $rules['telegram_username'] = ['nullable', 'string', 'max:100'];
            $rules['whatsapp_number'] = ['nullable', 'string', 'max:50'];
        }

        if ($step === 3 || $action === 'complete') {
            if (!$isCurrencyLocked) {
                $rules['preferred_currency'] = ['required', 'string', 'size:3'];
            }
        }

        $validated = $request->validate($rules);

        if (isset($validated['telegram_username']) && $validated['telegram_username']) {
            $validated['telegram_username'] = ltrim($validated['telegram_username'], '@');
        }

        if ($isCurrencyLocked && isset($validated['preferred_currency'])) {
            unset($validated['preferred_currency']);
        }

        $user->fill($validated);

        if ($action === 'complete') {
            $user->onboarding_completed = true;
            if (!$isCurrencyLocked && $user->preferred_currency) {
                $user->preferred_currency_locked_at = now();
            }
        }

        $user->save();

        if ($action === 'complete') {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        return back()->with('status', 'Progress saved');
    }
}
