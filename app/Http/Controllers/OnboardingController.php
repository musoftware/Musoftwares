<?php

namespace App\Http\Controllers;

use App\Services\OnboardingService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OnboardingController extends Controller
{
    protected OnboardingService $onboardingService;

    public function __construct(OnboardingService $onboardingService)
    {
        $this->onboardingService = $onboardingService;
    }

    public function show(Request $request)
    {
        $user = $request->user();

        if ($user->onboarding_completed) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        $countries = $this->onboardingService->getCountries();
        $detectedCountry = $this->onboardingService->detectCountryFromIp($request->ip());

        return Inertia::render('Auth/OnboardingWizard', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'country' => $user->country ?: $detectedCountry,
                'city' => $user->city ?: '',
                'mobile_1' => $user->mobile_1 ?: '',
                'mobile_2' => $user->mobile_2 ?: '',
                'telegram_username' => $user->telegram_username ?: '',
            ],
            'countries' => $countries,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

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
        }

        $validated = $request->validate($rules);

        if (isset($validated['telegram_username']) && $validated['telegram_username']) {
            $validated['telegram_username'] = ltrim($validated['telegram_username'], '@');
        }

        $isComplete = ($action === 'complete');
        $this->onboardingService->saveOnboardingStep($user, $validated, $isComplete);

        if ($action === 'complete') {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        return back()->with('status', __('general.progress_saved'));
    }

    public function updateTourStatus(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'step' => 'nullable|integer|min:1|max:7',
            'skipped' => 'nullable|boolean',
            'completed' => 'nullable|boolean',
            'reset' => 'nullable|boolean',
        ]);

        $this->onboardingService->updateTourStatus($user, $validated);

        if (isset($validated['reset']) && $validated['reset']) {
            return response()->json(['status' => 'tour_reset', 'user' => $user]);
        }

        return response()->json(['status' => 'success', 'user' => $user]);
    }

    public function getCities(Request $request, $countryName)
    {
        $cities = $this->onboardingService->getCities($countryName);

        return response()->json($cities);
    }
}
