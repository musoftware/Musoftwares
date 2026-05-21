<?php

$models = [
    'Currency' => 'Core',
    'Invoice' => 'ERP',
    'InvoiceItem' => 'ERP',
    'Project' => 'ERP',
    'TeamMember' => 'ERP',
    'Contract' => 'Freelance',
    'Job' => 'Freelance',
    'Service' => 'Marketplace',
    'ServiceCategory' => 'Marketplace',
    'ServiceOrder' => 'Marketplace',
    'ServicePackage' => 'Marketplace',
];

$appModelsDir = __DIR__ . '/app/Models';
$modulesDir = __DIR__ . '/Modules';

// 1. Move files and update namespaces
foreach ($models as $modelName => $module) {
    $sourcePath = $appModelsDir . '/' . $modelName . '.php';
    $targetDir = $modulesDir . '/' . $module . '/Models';
    $targetPath = $targetDir . '/' . $modelName . '.php';

    if (file_exists($sourcePath)) {
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        // Delete the V2 modular version if it exists
        if (file_exists($targetPath)) {
            unlink($targetPath);
            echo "Deleted V2 module model: $targetPath\n";
        }

        // Move legacy model to module path
        rename($sourcePath, $targetPath);
        echo "Moved $modelName.php to $module/Models\n";

        // Update namespace
        $content = file_get_contents($targetPath);
        $newNamespace = "namespace Modules\\$module\\Models;";
        
        // Handle namespace update. It could be `namespace App\Models;`
        $content = preg_replace('/namespace\s+App\\\Models;/', $newNamespace, $content);
        file_put_contents($targetPath, $content);
        echo "Updated namespace to $newNamespace in $modelName.php\n";
    } else {
        echo "Source file not found: $sourcePath\n";
    }
}

// 2. Global Find and Replace
$directoriesToScan = [
    __DIR__ . '/app',
    __DIR__ . '/Modules',
    __DIR__ . '/routes',
];

function scanAndReplace($dir, $models) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $path = $file->getRealPath();
            $content = file_get_contents($path);
            $newContent = $content;

            foreach ($models as $modelName => $module) {
                $oldNamespace = "App\\Models\\$modelName";
                $newNamespace = "Modules\\$module\\Models\\$modelName";
                
                // Replace `App\Models\Invoice` with `Modules\ERP\Models\Invoice`
                $newContent = str_replace($oldNamespace, $newNamespace, $newContent);
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
        scanAndReplace($dir, $models);
    }
}

echo "Done.\n";
