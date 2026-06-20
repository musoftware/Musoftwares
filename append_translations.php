<?php
function appendTranslation($file, $key, $val) {
    $content = file_get_contents($file);
    $content = rtrim($content);
    $pos = strrpos($content, '];');
    if ($pos !== false) {
        // Escape single quotes in value
        $val = str_replace("'", "\'", $val);
        $replacement = "    '$key' => '$val',\n];\n";
        $content = substr_replace($content, $replacement, $pos, 2);
        file_put_contents($file, $content);
        echo "Appended to $file\n";
    }
}

appendTranslation('d:\Projects\1AOrganized\PhpProject\MusoftwareBusiness\Musoftwares\lang\en\general.php', 'view_user_financial_transactions', 'View User Financial Transactions');
appendTranslation('d:\Projects\1AOrganized\PhpProject\MusoftwareBusiness\Musoftwares\lang\ar\general.php', 'view_user_financial_transactions', 'عرض المعاملات المالية للمستخدم');

appendTranslation('d:\Projects\1AOrganized\PhpProject\MusoftwareBusiness\Musoftwares\lang\en\gold_saver.php', 'todays_prices_egp', 'Today\'s Gold Prices');
appendTranslation('d:\Projects\1AOrganized\PhpProject\MusoftwareBusiness\Musoftwares\lang\ar\gold_saver.php', 'todays_prices_egp', 'أسعار الذهب اليوم');
