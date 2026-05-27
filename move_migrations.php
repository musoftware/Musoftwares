<?php
$base = __DIR__;
$files = [
    '2026_05_25_165300_create_crm_activities_table.php' => 'CRM',
    '2026_05_25_170100_create_crm_webhooks_table.php' => 'CRM',
    '2026_05_25_210904_create_booking_advanced_rules_tables.php' => 'Booking',
    '2026_05_25_212500_create_booking_priority_tables.php' => 'Booking',
    '2026_05_25_213500_create_booking_smart_slots_tables.php' => 'Booking',
    '2026_05_26_000000_create_crm_whatsapp_inbox_tables.php' => 'CRM',
    '2026_05_26_000000_recreate_service_landing_pages_table.php' => 'Marketplace',
    '2026_05_26_100000_create_crm_whatsapp_campaign_tables.php' => 'CRM',
    '2026_05_26_180000_make_tenant_id_nullable_in_erp_tables.php' => 'ERP',
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
