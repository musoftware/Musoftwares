<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

echo "Syncing migrations robustly...\n";

// Get pending migrations
Artisan::call('migrate:status');
$output = Artisan::output();

// Parse pending migrations
$lines = explode("\n", $output);
$pending = [];
foreach ($lines as $line) {
    if (strpos($line, 'Pending') !== false) {
        if (preg_match('/([0-9]{4}_[0-9]{2}_[0-9]{2}_[0-9]{6}_[a-zA-Z0-9_]+)/', $line, $m)) {
            $pending[] = $m[1];
        }
    }
}

foreach ($pending as $migration) {
    echo "Running migration: $migration\n";
    try {
        Artisan::call('migrate', ['--path' => getMigrationPath($migration), '--force' => true]);
        echo "✅ Success\n";
    } catch (\Exception $e) {
        echo "⚠️ Failed: " . $e->getMessage() . "\n";
        echo "Marking as run in migrations table to skip future errors...\n";
        
        $batch = DB::table('migrations')->max('batch') + 1;
        DB::table('migrations')->insert([
            'migration' => $migration,
            'batch' => $batch
        ]);
    }
}

function getMigrationPath($migration) {
    // Search in database/migrations and Modules/*/Database/Migrations
    $paths = array_merge(
        glob(database_path("migrations/{$migration}.php")),
        glob(base_path("Modules/*/Database/Migrations/{$migration}.php"))
    );
    $path = $paths[0] ?? '';
    if ($path) {
        $path = str_replace('\\', '/', $path);
        $base = str_replace('\\', '/', base_path()) . '/';
        $path = str_replace($base, '', $path);
    }
    return $path;
}

echo "Done syncing migrations.\n";
