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



        try {
            $countries = \Illuminate\Support\Facades\DB::table('countries')->pluck('name')->toArray();
            if (empty($countries)) {
                \Illuminate\Support\Facades\Artisan::call('db:seed', [
                    '--class' => 'Database\\Seeders\\CountrySeeder',
                    '--force' => true,
                ]);
                $countries = \Illuminate\Support\Facades\DB::table('countries')->pluck('name')->toArray();
            }
        } catch (\Illuminate\Database\QueryException $e) {
            // If table doesn't exist, run the migration and seeder automatically
            if (str_contains($e->getMessage(), 'Base table or view not found')) {
                \Illuminate\Support\Facades\Artisan::call('migrate', [
                    '--path' => 'database/migrations/2026_05_23_150011_create_countries_table.php',
                    '--force' => true,
                ]);
                \Illuminate\Support\Facades\Artisan::call('db:seed', [
                    '--class' => 'Database\\Seeders\\CountrySeeder',
                    '--force' => true,
                ]);
                $countries = \Illuminate\Support\Facades\DB::table('countries')->pluck('name')->toArray();
            } else {
                throw $e;
            }
        }

        $detectedCountry = 'United States'; // Fallback
        
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

        $user->fill($validated);

        if ($action === 'complete') {
            $user->onboarding_completed = true;
        }

        try {
            $user->save();
        } catch (\Illuminate\Database\QueryException $e) {
            if (str_contains($e->getMessage(), 'Column not found') || str_contains($e->getMessage(), 'Unknown column')) {
                \Illuminate\Support\Facades\Artisan::call('migrate', [
                    '--path' => 'database/migrations/2026_05_23_150449_add_onboarding_fields_to_users_table.php',
                    '--force' => true,
                ]);
                $user->save();
            } else {
                throw $e;
            }
        }

        if ($action === 'complete') {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        return back()->with('status', 'Progress saved');
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

        if (isset($validated['reset']) && $validated['reset']) {
            $user->tour_completed = false;
            $user->tour_skipped = false;
            $user->current_tour_step = 1;
            $user->save();
            return response()->json(['status' => 'tour_reset', 'user' => $user]);
        }

        if (isset($validated['step'])) {
            $user->current_tour_step = $validated['step'];
        }

        if (isset($validated['skipped']) && $validated['skipped']) {
            $user->tour_skipped = true;
        }

        if (isset($validated['completed']) && $validated['completed']) {
            $user->tour_completed = true;
            $user->current_tour_step = 7;
        }

        $user->save();

        return response()->json(['status' => 'success', 'user' => $user]);
    }

    public function getCities(Request $request, $countryName)
    {
        $country = \Illuminate\Support\Facades\DB::table('countries')
            ->where('name', $countryName)
            ->first();

        if (!$country) {
            return response()->json([]);
        }

        try {
            $cities = \Illuminate\Support\Facades\DB::table('cities')
                ->where('country_id', $country->id)
                ->distinct()
                ->pluck('name')
                ->toArray();
                
            if (\Illuminate\Support\Facades\DB::table('cities')->count() === 0) {
                \Illuminate\Support\Facades\Artisan::call('db:seed', [
                    '--class' => 'Database\\Seeders\\CitySeeder',
                    '--force' => true,
                ]);
                $cities = \Illuminate\Support\Facades\DB::table('cities')
                    ->where('country_id', $country->id)
                    ->distinct()
                    ->pluck('name')
                    ->toArray();
            }
        } catch (\Illuminate\Database\QueryException $e) {
            if (str_contains($e->getMessage(), 'Base table or view not found')) {
                \Illuminate\Support\Facades\Artisan::call('migrate', [
                    '--path' => 'database/migrations/2026_05_23_150824_create_cities_table.php',
                    '--force' => true,
                ]);
                \Illuminate\Support\Facades\Artisan::call('db:seed', [
                    '--class' => 'Database\\Seeders\\CitySeeder',
                    '--force' => true,
                ]);
                $cities = \Illuminate\Support\Facades\DB::table('cities')
                    ->where('country_id', $country->id)
                    ->distinct()
                    ->pluck('name')
                    ->toArray();
            } else {
                throw $e;
            }
        }

        return response()->json($cities);
    }
}
