<?php
use Illuminate\Contracts\Http\Kernel;
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

try {
    \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
    echo "Migrated.\n";
    
    // Check if countries are seeded
    if (\Illuminate\Support\Facades\DB::table('countries')->count() === 0) {
        \Illuminate\Support\Facades\Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\CountrySeeder', '--force' => true]);
        echo "Countries seeded.\n";
    }

    // Check if cities are seeded
    if (\Illuminate\Support\Facades\DB::table('cities')->count() === 0) {
        \Illuminate\Support\Facades\Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\CitySeeder', '--force' => true]);
        echo "Cities seeded.\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n" . $e->getTraceAsString();
}
