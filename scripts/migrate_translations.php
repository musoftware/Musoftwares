<?php

$langDir = __DIR__ . '/../lang';
$enJsonPath = $langDir . '/en.json';
$arJsonPath = $langDir . '/ar.json';

if (!file_exists($enJsonPath)) {
    echo "en.json not found.\n";
    exit;
}

$enStrings = json_decode(file_get_contents($enJsonPath), true) ?: [];
$arStrings = file_exists($arJsonPath) ? json_decode(file_get_contents($arJsonPath), true) : [];

$mapping = []; // "Old String" => "domain.new_key"
$newEnArrays = []; // "domain" => ["new_key" => "Translation"]
$newArArrays = [];

function generateKey($phrase) {
    $phrase = strtolower($phrase);
    $phrase = preg_replace('/[^a-z0-9\s]/', '', $phrase);
    $words = array_filter(explode(' ', $phrase));
    $words = array_slice($words, 0, 5); // take up to 5 words
    $key = implode('_', $words);
    if (empty($key)) return 'text';
    return $key;
}

function assignDomain($phrase) {
    $phraseLower = strtolower($phrase);
    if (preg_match('/\b(invoice|invoices|project|projects|client|clients|wallet|wallets|ledger|budget|transaction|transactions|cost|costs|expense|expenses|debt|debts)\b/', $phraseLower)) return 'erp';
    if (preg_match('/\b(contract|contracts|job|jobs|proposal|proposals|bid|bids|freelance|connect|connects|hire|hired)\b/', $phraseLower)) return 'freelance';
    if (preg_match('/\b(gold|gram|grams|ounce|karat|purity)\b/', $phraseLower)) return 'gold_saver';
    if (preg_match('/\b(sms|gateway)\b/', $phraseLower)) return 'sms_gateway';
    if (preg_match('/\b(admin|settings)\b/', $phraseLower)) return 'admin';
    if (preg_match('/\b(billing|subscription|plan)\b/', $phraseLower)) return 'billing';
    if (preg_match('/\b(payment|checkout|pay)\b/', $phraseLower)) return 'payment';
    return 'general';
}

echo "Generating keys and domains...\n";

foreach ($enStrings as $oldStr => $enTrans) {
    $domain = assignDomain($oldStr);
    $baseKey = generateKey($oldStr);
    $key = $baseKey;
    
    // Ensure uniqueness within domain
    $counter = 2;
    while (isset($newEnArrays[$domain][$key])) {
        $key = $baseKey . '_' . $counter;
        $counter++;
    }
    
    $mapping[$oldStr] = "$domain.$key";
    $newEnArrays[$domain][$key] = $enTrans;
    $newArArrays[$domain][$key] = $arStrings[$oldStr] ?? $enTrans;
}

echo "Writing to PHP arrays...\n";

// 1. Merge into PHP files
$locales = ['en' => $newEnArrays, 'ar' => $newArArrays];
foreach ($locales as $locale => $domainArrays) {
    foreach ($domainArrays as $domain => $translations) {
        $phpFile = $langDir . '/' . $locale . '/' . $domain . '.php';
        $existing = [];
        if (file_exists($phpFile)) {
            $existing = require $phpFile;
            if (!is_array($existing)) $existing = [];
        }
        $merged = array_merge($existing, $translations);
        
        $export = var_export($merged, true);
        // Basic str_replace to convert array() to []
        $export = str_replace('array (', '[', $export);
        $export = preg_replace('/^\s+\),/m', '],', $export);
        $export = preg_replace('/^\)$/m', ']', $export);

        $content = "<?php\n\nreturn " . $export . ";\n";
        file_put_contents($phpFile, $content);
    }
}

echo "Refactoring Codebase...\n";

// 2. Refactor Codebase
$dirsToScan = [
    __DIR__ . '/../resources/js',
    __DIR__ . '/../resources/views',
    __DIR__ . '/../app',
    __DIR__ . '/../Modules',
    __DIR__ . '/../wordpress-plugins'
];

$filesReplacedCount = 0;

foreach ($dirsToScan as $dir) {
    if (!is_dir($dir)) continue;
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $ext = $file->getExtension();
            if (in_array($ext, ['php', 'tsx', 'ts', 'js', 'jsx'])) {
                $content = file_get_contents($file->getPathname());
                $origContent = $content;
                
                foreach ($mapping as $oldStr => $newFullKey) {
                    $oldSingle = str_replace("'", "\\'", $oldStr);
                    $oldDouble = str_replace('"', '\\"', $oldStr);
                    
                    $patterns = [
                        "__('$oldSingle')" => "__('$newFullKey')",
                        "__(\"$oldDouble\")" => "__(\"$newFullKey\")",
                        "trans('$oldSingle')" => "trans('$newFullKey')",
                        "trans(\"$oldDouble\")" => "trans(\"$newFullKey\")",
                    ];
                    
                    $content = str_replace(array_keys($patterns), array_values($patterns), $content);
                }
                
                if ($content !== $origContent) {
                    file_put_contents($file->getPathname(), $content);
                    $filesReplacedCount++;
                }
            }
        }
    }
}

echo "Migration complete! Updated $filesReplacedCount files.\n";

