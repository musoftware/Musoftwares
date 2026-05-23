<?php
use Illuminate\Contracts\Http\Kernel;
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

\Illuminate\Support\Facades\Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\CountrySeeder', '--force' => true]);
echo \Illuminate\Support\Facades\DB::table('countries')->count() . " countries seeded.";
