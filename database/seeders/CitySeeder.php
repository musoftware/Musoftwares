<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class CitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // GitHub JSON where format is { "Country Name": ["City 1", "City 2"] }
        $response = Http::withoutVerifying()->timeout(30)->get('https://raw.githubusercontent.com/russ666/all-countries-and-cities-json/master/countries.json');
        
        if (!$response->successful()) {
            $this->command->error('Failed to fetch cities from GitHub.');
            return;
        }

        $data = $response->json();
        $now = Carbon::now();

        // Get all existing countries mapped by name
        $countries = DB::table('countries')->pluck('id', 'name')->toArray();

        foreach ($data as $countryName => $cities) {
            if (isset($countries[$countryName])) {
                $countryId = $countries[$countryName];
                
                $insertData = [];
                foreach ($cities as $city) {
                    $insertData[] = [
                        'country_id' => $countryId,
                        'name' => $city,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];

                    // Insert in chunks to avoid memory/query size issues
                    if (count($insertData) >= 500) {
                        DB::table('cities')->insertOrIgnore($insertData);
                        $insertData = [];
                    }
                }
                
                if (count($insertData) > 0) {
                    DB::table('cities')->insertOrIgnore($insertData);
                }
            }
        }
    }
}
