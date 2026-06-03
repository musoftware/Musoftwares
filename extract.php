<?php
$keys_landing = [
    'kbdny_title', 'kbdny_desc', 'stock_manager_title', 'stock_manager_desc', 
    'mini_fatora_title', 'mini_fatora_desc', 'vodafone_crm_title', 'vodafone_crm_desc', 
    'amc_academy_desc', 'project_manager_desc', 'telecom_system_title', 'telecom_system_desc', 
    'altayaraa_title', 'altayaraa_desc', 'forex_app_title', 'forex_app_desc', 
    'amc_social_desc', 'nokhpa_title', 'nokhpa_desc', 'duplicate_finder_title', 
    'duplicate_finder_desc', 'map_extractor_title', 'map_extractor_desc', 
    'instagram_manager_title', 'instagram_manager_desc', 'whatsapp_sender_title', 
    'whatsapp_sender_desc', 'telegram_sender_title', 'telegram_sender_desc', 
    'inbox_sender_title', 'inbox_sender_desc', 'heic_converter_title', 'heic_converter_desc', 
    'text_studio_title', 'text_studio_desc', 'amc_tasks_downloader_desc', 
    'stocktalk_ai_title', 'stocktalk_ai_desc', 'forex_bot_title', 'forex_bot_desc', 
    'revflow_title', 'revflow_desc', 'chartcash_title', 'chartcash_desc', 
    'fb_id_extractor_title', 'fb_id_extractor_desc', 'khamsat_notifier_title', 
    'khamsat_notifier_desc', 'am_email_controls_desc', 'amc_tasks_desc'
];
$keys_services = ['amc_tasks', 'amc_academy', 'amc_social', 'amc_tasks_downloader', 'am_email_controls'];
$keys_common = ['project_manager'];

$out = [];
$en_landing = require('D:\Projects\1AOrganized\PhpProject\MusoftwareBusiness\musoftwares.com\lang\en\landing.php');
$ar_landing = require('D:\Projects\1AOrganized\PhpProject\MusoftwareBusiness\musoftwares.com\lang\ar\landing.php');

$en_services = require('D:\Projects\1AOrganized\PhpProject\MusoftwareBusiness\musoftwares.com\lang\en\services.php');
$ar_services = require('D:\Projects\1AOrganized\PhpProject\MusoftwareBusiness\musoftwares.com\lang\ar\services.php');

$en_common = require('D:\Projects\1AOrganized\PhpProject\MusoftwareBusiness\musoftwares.com\lang\en\common.php');
$ar_common = require('D:\Projects\1AOrganized\PhpProject\MusoftwareBusiness\musoftwares.com\lang\ar\common.php');

foreach($keys_landing as $k) {
    $out['portfolio_' . $k] = ['en' => $en_landing[$k] ?? '', 'ar' => $ar_landing[$k] ?? ''];
}
foreach($keys_services as $k) {
    $out['portfolio_' . $k] = ['en' => $en_services[$k] ?? '', 'ar' => $ar_services[$k] ?? ''];
}
foreach($keys_common as $k) {
    $out['portfolio_' . $k] = ['en' => $en_common[$k] ?? '', 'ar' => $ar_common[$k] ?? ''];
}

file_put_contents('extracted_translations.json', json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Done\n";
