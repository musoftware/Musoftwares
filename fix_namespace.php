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
    if (strpos($c, 'use Illuminate\Foundation\Testing\DatabaseTransactions;') === false) {
        $c = str_replace('use Tests\TestCase;', "use Illuminate\Foundation\Testing\DatabaseTransactions;\nuse Tests\TestCase;", $c);
        file_put_contents($file, $c);
        echo "Fixed $file\n";
    }
}
