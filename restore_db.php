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

echo "\n3. Running migrations to update the schema...\n";
system('php force_sync_migrations.php');
echo "\n\n";

echo "\n3.5 Migrating old phone numbers to mobile_1 and mobile_2...\n";
try {
    // Migrate phone_number to mobile_1
    DB::statement("UPDATE users SET mobile_1 = phone_number WHERE (mobile_1 IS NULL OR mobile_1 = '') AND phone_number IS NOT NULL AND phone_number != '';");
    DB::statement("ALTER TABLE users DROP COLUMN phone_number;");
    echo "Migrated and dropped phone_number column.\n";
} catch (\Exception $e) {
    echo "No phone_number column to migrate (or already dropped): " . $e->getMessage() . "\n";
}

try {
    // Migrate phone_number2 to mobile_2
    DB::statement("UPDATE users SET mobile_2 = phone_number2 WHERE (mobile_2 IS NULL OR mobile_2 = '') AND phone_number2 IS NOT NULL AND phone_number2 != '';");
    DB::statement("ALTER TABLE users DROP COLUMN phone_number2;");
    echo "Migrated and dropped phone_number2 column.\n";
} catch (\Exception $e) {
    echo "No phone_number2 column to migrate (or already dropped): " . $e->getMessage() . "\n";
}

try {
    // Fallback: if mobile_1 is still empty, try to get it from whatsapp_number
    DB::statement("UPDATE users SET mobile_1 = whatsapp_number WHERE (mobile_1 IS NULL OR mobile_1 = '') AND whatsapp_number IS NOT NULL AND whatsapp_number != '';");
    // We do NOT drop whatsapp_number because the new schema might still use it
    echo "Migrated whatsapp_number to mobile_1 where mobile_1 was empty.\n";
} catch (\Exception $e) {
    echo "No whatsapp_number column to migrate.\n";
}

echo "✅ Database restore complete. Now running system verification tests...\n\n";

// ── Step 4: Run All Tests ─────────────────────────────────────────────
// Each suite is run individually to isolate failures to specific modules.

$suites = [
    'Unit'              => 'Core Unit Tests',
    'Feature'           => 'Admin Panel & Platform Feature Tests',
    'ERP'               => 'ERP Module (Clients, Invoices, Wallet, POS, Payroll, Inventory, MultiBranch)',
    'Freelance'         => 'Freelance Module (Jobs, Proposals, Contracts, Skills, Finance, Security)',
    'CRM'               => 'CRM Module (Leads, Campaigns, Sequences, WhatsApp)',
    'Marketplace'       => 'Marketplace Module (Services, Orders, Escrow)',
    'Booking'           => 'Booking Module (Reservations, Widgets, SMS, WhiteLabel)',
    'Tools'             => 'Tools Module (Licenses, Runtime Auth)',
    'GoldSavers'        => 'GoldSavers Module (Wallet, Prices, Calculations)',
    'SmsPaymentGateway' => 'SMS Payment Gateway Module (Parser, Matching, Spoof Detection)',
];

$results  = [];
$allPassed = true;

foreach ($suites as $suite => $description) {
    echo str_repeat('═', 70) . "\n";
    echo "  ▶ Testing: {$description}\n";
    echo "    Suite:   {$suite}\n";
    echo str_repeat('─', 70) . "\n";

    exec("C:\tools\php83\php.exe artisan test --testsuite=$suite --no-interaction", $outputLines, $exitCode);
    $output = implode("\n", $outputLines);
    echo $output . "\n";

    if ($exitCode === 0) {
        $results[$suite] = '✅ PASSED';
    } else {
        $results[$suite] = '❌ FAILED';
        $allPassed = false;
    }

    echo "\n";
}

// ── Summary Report ────────────────────────────────────────────────────
echo "\n" . str_repeat('═', 70) . "\n";
echo "  📊 POST-RESTORE TEST SUMMARY\n";
echo str_repeat('═', 70) . "\n\n";

foreach ($results as $suite => $status) {
    $desc = $suites[$suite];
    echo "  {$status}  [{$suite}] {$desc}\n";
}

echo "\n" . str_repeat('─', 70) . "\n";

if ($allPassed) {
    echo "  🎉 ALL SYSTEMS PASSED — Database restore verified successfully.\n";
} else {
    echo "  ⚠️  SOME SYSTEMS FAILED — Review the output above and fix before deploying.\n";
}

echo str_repeat('═', 70) . "\n";

