<?php

$srcDir = __DIR__ . '/app/Helper';
$destDir = __DIR__ . '/app/Helpers';

if (!is_dir($destDir)) {
    mkdir($destDir, 0755, true);
}

// 1. Move files
if (is_dir($srcDir)) {
    $files = glob($srcDir . '/*.php');
    foreach ($files as $file) {
        $basename = basename($file);
        if ($basename === 'KashierHelper.php') {
            // Delete old one, we keep the one in app/Helpers
            unlink($file);
            echo "Deleted legacy KashierHelper.php\n";
            continue;
        }
        
        rename($file, $destDir . '/' . $basename);
        echo "Moved $basename to app/Helpers\n";
    }
    // Try to remove old dir if empty
    @rmdir($srcDir);
}

// 2. Update namespace in all Helpers
$helperFiles = glob($destDir . '/*.php');
foreach ($helperFiles as $file) {
    $content = file_get_contents($file);
    if (strpos($content, 'namespace App\Helper;') !== false) {
        $content = str_replace('namespace App\Helper;', 'namespace App\Helpers;', $content);
        file_put_contents($file, $content);
        echo "Updated namespace in " . basename($file) . "\n";
    }
}

// 3. Global Find and Replace
$directoriesToScan = [
    __DIR__ . '/app',
    __DIR__ . '/Modules',
];

function scanAndReplace($dir) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $path = $file->getRealPath();
            // Skip the Helper/Helpers directories to avoid re-modifying what we just did
            if (strpos($path, DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Helpers' . DIRECTORY_SEPARATOR) !== false || 
                strpos($path, DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Helper' . DIRECTORY_SEPARATOR) !== false) {
                continue;
            }

            $content = file_get_contents($path);
            
            // Look for `App\Helper\` and replace with `App\Helpers\`
            // This catches `use App\Helper\TimerHelper;` and `\App\Helper\TimerHelper::instance()`
            $newContent = str_replace('App\Helper\\', 'App\Helpers\\', $content);
            
            if ($newContent !== $content) {
                file_put_contents($path, $newContent);
                echo "Replaced App\Helper in " . $file->getFilename() . "\n";
            }
        }
    }
}

foreach ($directoriesToScan as $dir) {
    if (is_dir($dir)) {
        scanAndReplace($dir);
    }
}

echo "Done.\n";
