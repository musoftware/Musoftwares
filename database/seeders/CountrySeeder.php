<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class CountrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $response = Http::withoutVerifying()->get('https://raw.githubusercontent.com/samayo/country-json/master/src/country-by-name.json');
        
        if (!$response->successful()) {
            $this->command->error('Failed to fetch countries from GitHub.');
            return;
        }

        $countriesData = $response->json();
        $now = Carbon::now();
        $insertData = [];

        foreach ($countriesData as $country) {
            if (isset($country['country'])) {
                $insertData[] = [
                    'name' => $country['country'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('countries')->insertOrIgnore($insertData);
    }
}
