<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

echo "1. Dropping all tables (Cleaning Database)...\n";
Artisan::call('db:wipe', ['--force' => true]);
echo trim(Artisan::output()) . "\n\n";

echo "2. Importing SQL File...\n";
$sqlPath = __DIR__.'/u962989541_db (4).sql';

if (!file_exists($sqlPath)) {
    die("ERROR: SQL File not found: {$sqlPath}\n");
}

$dbHost = config('database.connections.mysql.host', '127.0.0.1');
$dbPort = config('database.connections.mysql.port', '3306');
$dbName = config('database.connections.mysql.database');
$dbUser = config('database.connections.mysql.username');
$dbPass = config('database.connections.mysql.password');

$passArg = $dbPass ? "-p\"$dbPass\"" : "";

// Find mysql.exe locally to bypass PHP memory limits for massive SQL files
$possiblePaths = [
    'D:\laragon\bin\mysql-8.0.30-winx64\bin\mysql.exe',
    'D:\laragon\bin\mysql\mysql-8.0.30-winx64\bin\mysql.exe',
    'C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin\mysql.exe',
    'C:\xampp\mysql\bin\mysql.exe',
    'mysql' // fallback to global PATH
];

$mysqlExe = 'mysql';
foreach ($possiblePaths as $path) {
    if (@file_exists($path)) {
        $mysqlExe = '"' . $path . '"';
        break;
    }
}

echo "Using MySQL Executable: $mysqlExe\n";
$command = "{$mysqlExe} -h {$dbHost} -P {$dbPort} -u {$dbUser} {$passArg} --init-command=\"SET SESSION FOREIGN_KEY_CHECKS=0;\" {$dbName} < \"{$sqlPath}\"";

system($command, $returnVar);

if ($returnVar !== 0) {
    echo "\nWARNING: MySQL import returned code {$returnVar}. Falling back to PHP PDO method...\n";
    ini_set('memory_limit', '-1');
    try {
        DB::unprepared("SET SESSION FOREIGN_KEY_CHECKS=0;");
        DB::unprepared(file_get_contents($sqlPath));
        DB::unprepared("SET SESSION FOREIGN_KEY_CHECKS=1;");
        echo "PDO Import successful.\n";
    } catch (\Exception $e) {
        die("PDO Import failed: " . $e->getMessage() . "\n");
    }
} else {
echo "SQL Import finished successfully.\n";
}

echo "\n2.5 Migrating whatsapp_number to mobile_1 (if exists)...\n";
try {
    DB::statement("UPDATE users SET mobile_1 = whatsapp_number WHERE (mobile_1 IS NULL OR mobile_1 = '') AND whatsapp_number IS NOT NULL AND whatsapp_number != '';");
    DB::statement("ALTER TABLE users DROP COLUMN whatsapp_number;");
    echo "Migrated and dropped whatsapp_number column.\n";
} catch (\Exception $e) {
    echo "No whatsapp_number column to migrate (or already dropped).\n";
}
echo "\n3. Running migrations to update the schema...\n";
Artisan::call('migrate', ['--force' => true]);
echo trim(Artisan::output()) . "\n\n";

echo "✅ All Done! Database is clean, imported, and migrated.\n";
