<?php
$base = __DIR__;
$files = [
    '2026_05_26_000000_add_commission_to_musoftware_payments_table.php' => 'MusoftwarePayments',
    '2024_01_01_000001_create_musoftware_payments_table.php' => 'MusoftwarePayments',
];

foreach ($files as $file => $module) {
    $source = $base . '/database/migrations/' . $file;
    $destDir = $base . '/Modules/' . $module . '/Database/Migrations';
    if (!is_dir($destDir)) {
        mkdir($destDir, 0755, true);
    }
    $dest = $destDir . '/' . $file;
    if (file_exists($source)) {
        if (rename($source, $dest)) {
            echo "Moved $file to $module\n";
        } else {
            echo "Failed to move $file\n";
        }
    } else {
        echo "Source not found: $file\n";
    }
}
echo "Done.\n";
