<?php
$dir = __DIR__ . '/database/migrations';
$files = scandir($dir);

$patterns = [
    '/create_shop_.*_table\.php$/',
    '/add_.*_to_shop_.*_table\.php$/',
    '/create_gold_prices_table\.php$/',
    '/create_gold_world_prices_table\.php$/',
    '/add_22k_carat_to_gold_savers_table\.php$/',
    '/create_service_.*_table\.php$/',
    '/add_.*_to_service.*_table\.php$/',
    '/create_membership_.*_table\.php$/',
    '/add_.*_to_membership.*_table\.php$/',
    '/change_services_description_to_longtext\.php$/',
    '/update_slugs_in_services_table\.php$/'
];

// Exceptions to NEVER delete
$exceptions = [
    '2026_05_26_170000_drop_legacy_memberships_and_software_tables.php',
    '2026_05_26_150000_drop_job_listings_and_bids_tables.php'
];

$deletedCount = 0;

foreach ($files as $file) {
    if ($file === '.' || $file === '..') continue;

    // Do not delete drop migrations
    if (str_contains($file, 'drop_')) continue;
    if (in_array($file, $exceptions)) continue;

    $shouldDelete = false;
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $file)) {
            $shouldDelete = true;
            break;
        }
    }

    if ($shouldDelete) {
        echo "Deleting: $file\n";
        unlink($dir . '/' . $file);
        $deletedCount++;
    }
}

echo "Total deleted: $deletedCount\n";
