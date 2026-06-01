<?php
$files = [
    'Modules/ERP/Tests/Feature/ClientControllerTest.php',
    'Modules/ERP/Tests/Feature/PosTest.php',
    'Modules/ERP/Tests/Feature/ProjectControllerTest.php',
    'Modules/ERP/Tests/Feature/SettingsTest.php',
    'Modules/ERP/Tests/Feature/TeamAuthAndOversightTest.php',
    'Modules/ERP/Tests/Feature/WalletControllerTest.php',
    'Modules/Tools/Tests/Feature/LicenseControllerTest.php'
];

foreach ($files as $file) {
    $c = file_get_contents($file);
    $c = preg_replace('/\$this->artisan\(\'migrate:fresh\'[\s\S]*?\]\);/', '// Removed migrate:fresh', $c);
    $c = str_replace('use Illuminate\Foundation\Testing\DatabaseMigrations;', 'use Illuminate\Foundation\Testing\DatabaseTransactions;', $c);
    $c = str_replace('use DatabaseMigrations;', 'use DatabaseTransactions;', $c);
    
    // Also check if DatabaseTransactions is not present inside the class body but needs to be added
    if (strpos($c, 'use DatabaseTransactions;') === false) {
        $c = preg_replace('/class\s+[A-Za-z0-9_]+\s+extends\s+TestCase\s*{/', "$0\n    use DatabaseTransactions;", $c);
    }
    
    file_put_contents($file, $c);
    echo "Fixed $file\n";
}
