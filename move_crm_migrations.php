<?php

$sourceDir = __DIR__ . '/database/migrations';
$targetDir = __DIR__ . '/Modules/CRM/Database/Migrations';

$files = scandir($sourceDir);

$patterns = [
    '/whatsapp/',
    '/lead/',
    '/campaign/',
    '/sequen/', // sequence, sequences
    '/crm/',
];

$movedCount = 0;

foreach ($files as $file) {
    if ($file === '.' || $file === '..') continue;

    $shouldMove = false;
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $file)) {
            $shouldMove = true;
            break;
        }
    }

    if ($shouldMove) {
        echo "Moving: $file\n";
        rename($sourceDir . '/' . $file, $targetDir . '/' . $file);
        $movedCount++;
    }
}

echo "Total moved: $movedCount\n";
