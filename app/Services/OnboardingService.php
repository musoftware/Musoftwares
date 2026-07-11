<?php

namespace App\Services;

use App\Models\User;
use GeoIp2\Database\Reader;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class OnboardingService extends BaseService
{
    /**
     * Get the list of all countries, running the migration and seeder if necessary.
     */
    public function getCountries(): array
    {
        try {
            $countries = DB::table('countries')->pluck('name')->toArray();
            if (empty($countries)) {
                Artisan::call('db:seed', [
                    '--class' => 'Database\\Seeders\\CountrySeeder',
                    '--force' => true,
                ]);
                $countries = DB::table('countries')->pluck('name')->toArray();
            }

            return $countries;
        } catch (QueryException $e) {
            // If table doesn't exist, run the migration and seeder automatically
            if (str_contains($e->getMessage(), 'Base table or view not found')) {
                Artisan::call('migrate', [
                    '--path' => 'database/migrations/2026_05_23_150011_create_countries_table.php',
                    '--force' => true,
                ]);
                Artisan::call('db:seed', [
                    '--class' => 'Database\\Seeders\\CountrySeeder',
                    '--force' => true,
                ]);

                return DB::table('countries')->pluck('name')->toArray();
            } else {
                throw $e;
            }
        }
    }

    /**
     * Get the list of cities for a given country name, running the migration and seeder if necessary.
     */
    public function getCities(string $countryName): array
    {
        $country = DB::table('countries')->where('name', $countryName)->first();

        if (! $country) {
            return [];
        }

        try {
            $cities = DB::table('cities')
                ->where('country_id', $country->id)
                ->distinct()
                ->pluck('name')
                ->toArray();

            if (DB::table('cities')->count() === 0) {
                Artisan::call('db:seed', [
                    '--class' => 'Database\\Seeders\\CitySeeder',
                    '--force' => true,
                ]);
                $cities = DB::table('cities')
                    ->where('country_id', $country->id)
                    ->distinct()
                    ->pluck('name')
                    ->toArray();
            }

            return $cities;
        } catch (QueryException $e) {
            if (str_contains($e->getMessage(), 'Base table or view not found')) {
                Artisan::call('migrate', [
                    '--path' => 'database/migrations/2026_05_23_150824_create_cities_table.php',
                    '--force' => true,
                ]);
                Artisan::call('db:seed', [
                    '--class' => 'Database\\Seeders\\CitySeeder',
                    '--force' => true,
                ]);

                return DB::table('cities')
                    ->where('country_id', $country->id)
                    ->distinct()
                    ->pluck('name')
                    ->toArray();
            } else {
                throw $e;
            }
        }
    }

    /**
     * Detect user's country using their IP address via GeoIP.
     */
    public function detectCountryFromIp(string $ip): string
    {
        $detectedCountry = 'United States'; // Fallback

        $geoDbPath = storage_path('app/geoip.mmdb');
        if (file_exists($geoDbPath)) {
            try {
                $reader = new Reader($geoDbPath);
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

        return $detectedCountry;
    }

    /**
     * Save the user's onboarding step, running migrations if columns are missing.
     */
    public function saveOnboardingStep(User $user, array $validatedData, bool $isComplete): void
    {
        $user->fill($validatedData);

        if ($isComplete) {
            $user->onboarding_completed = true;
        }

        try {
            $user->save();
        } catch (QueryException $e) {
            if (str_contains($e->getMessage(), 'Column not found') || str_contains($e->getMessage(), 'Unknown column')) {
                Artisan::call('migrate', [
                    '--path' => 'database/migrations/2026_05_23_150449_add_onboarding_fields_to_users_table.php',
                    '--force' => true,
                ]);
                $user->save();
            } else {
                throw $e;
            }
        }
    }

    /**
     * Update the user's tour status.
     */
    public function updateTourStatus(User $user, array $validatedData): void
    {
        if (isset($validatedData['reset']) && $validatedData['reset']) {
            $user->tour_completed = false;
            $user->tour_skipped = false;
            $user->current_tour_step = 1;
            $user->save();

            return;
        }

        if (isset($validatedData['step'])) {
            $user->current_tour_step = $validatedData['step'];
        }

        if (isset($validatedData['skipped']) && $validatedData['skipped']) {
            $user->tour_skipped = true;
        }

        if (isset($validatedData['completed']) && $validatedData['completed']) {
            $user->tour_completed = true;
            $user->current_tour_step = 7;
        }

        $user->save();
    }
}
