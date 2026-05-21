<?php

$sourceDir = __DIR__ . '/app/Models/CRM';
$destDir = __DIR__ . '/Modules/CRM/Models';

if (!is_dir($sourceDir)) {
    die("Source directory not found: $sourceDir\n");
}

if (!is_dir($destDir)) {
    mkdir($destDir, 0755, true);
}

// 1. Move files
$iterator = new DirectoryIterator($sourceDir);
$movedFiles = [];

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $basename = $file->getFilename();
        $destPath = $destDir . '/' . $basename;
        
        rename($file->getRealPath(), $destPath);
        $movedFiles[] = basename($basename, '.php');
        
        // Update namespace in the file itself
        $content = file_get_contents($destPath);
        $content = preg_replace('/namespace\s+App\\\Models\\\CRM;/', "namespace Modules\\CRM\\Models;", $content);
        file_put_contents($destPath, $content);
        
        echo "Moved $basename to Modules/CRM/Models\n";
    }
}

// 2. Global replace
$directoriesToScan = [
    __DIR__ . '/app',
    __DIR__ . '/Modules',
    __DIR__ . '/routes',
    __DIR__ . '/resources',
];

function scanAndReplace($dir, $movedFiles) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($iterator as $file) {
        if ($file->isFile() && in_array($file->getExtension(), ['php', 'jsx', 'tsx'])) {
            $path = $file->getRealPath();
            $content = file_get_contents($path);
            $newContent = $content;

            foreach ($movedFiles as $modelName) {
                // PHP namespace usages
                $oldNamespace = "App\\Models\\CRM\\$modelName";
                $newNamespace = "Modules\\CRM\\Models\\$modelName";
                
                // Regular replace for fully qualified
                $newContent = str_replace($oldNamespace, $newNamespace, $newContent);
                
                // Also check for string usage
                $oldString1 = "'App\\\\Models\\\\CRM\\\\$modelName'";
                $newString1 = "'Modules\\\\CRM\\\\Models\\\\$modelName'";
                $newContent = str_replace($oldString1, $newString1, $newContent);

                $oldString2 = '"App\\\\Models\\\\CRM\\\\' . $modelName . '"';
                $newString2 = '"Modules\\\\CRM\\\\Models\\\\' . $modelName . '"';
                $newContent = str_replace($oldString2, $newString2, $newContent);
            }
            
            if ($newContent !== $content) {
                file_put_contents($path, $newContent);
                echo "Updated references in " . $file->getFilename() . "\n";
            }
        }
    }
}

foreach ($directoriesToScan as $dir) {
    if (is_dir($dir)) {
        scanAndReplace($dir, $movedFiles);
    }
}

// Delete the old empty directory
@rmdir($sourceDir);

echo "Done.\n";
