<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=newmusoftware', 'root', '');
$pdo->exec('SET FOREIGN_KEY_CHECKS=0');

$tables = [
    'tool_downloads',
    'activated_devices',
    'tool_licenses',
    'tool_subscriptions',
    'tool_screenshots',
    'tool_versions',
    'tool_pricing_plans',
    'tools'
];

foreach ($tables as $table) {
    try {
        $pdo->exec("TRUNCATE TABLE `$table`");
    } catch (Exception $e) {
        // Ignore if table does not exist
    }
}

$pdo->exec('SET FOREIGN_KEY_CHECKS=1');
echo "Truncated existing tools-related tables.\n";
