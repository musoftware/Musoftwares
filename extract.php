<?php
$en = include 'd:/Projects/1AOrganized/PhpProject/MusoftwareBusiness/musoftwares.com/lang/en/landing.php';
$ar = include 'd:/Projects/1AOrganized/PhpProject/MusoftwareBusiness/musoftwares.com/lang/ar/landing.php';

$keys = [
    'privacy_meta_title', 'privacy_meta_desc', 'privacy_meta_keywords', 'privacy_title', 'privacy_subtitle',
    'last_updated_dec_2025', 'privacy_intro_title', 'privacy_intro_body', 'privacy_data_collect_title',
    'privacy_data_collect_body', 'privacy_data_use_title', 'privacy_data_use_body', 'privacy_data_security_title',
    'privacy_data_security_body', 'privacy_contact_title', 'privacy_contact_body', 'terms_meta_title',
    'terms_meta_desc', 'terms_meta_keywords', 'terms_title', 'terms_subtitle', 'terms_agreement_title',
    'terms_agreement_body', 'terms_ip_rights_title', 'terms_ip_rights_body', 'terms_user_rep_title',
    'terms_user_rep_body', 'terms_prohibited_title', 'terms_prohibited_body'
];

$outEn = [];
$outAr = [];

foreach ($keys as $k) {
    if (isset($en[$k])) $outEn[$k] = $en[$k];
    if (isset($ar[$k])) $outAr[$k] = $ar[$k];
}

file_put_contents('en_legal.json', json_encode($outEn, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
file_put_contents('ar_legal.json', json_encode($outAr, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Done\n";
