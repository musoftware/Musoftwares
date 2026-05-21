<?php

// 1. Run git restore on Modules/ to revert V2 modular models ONLY
exec('git restore Modules/');

$coreModelsDir = __DIR__ . '/Modules/Core/Models';
if (!is_dir($coreModelsDir)) {
    mkdir($coreModelsDir, 0755, true);
}

// 2. The 10 models that were moved incorrectly in the last script (excluding Job.php which was requested to be deleted)
$legacyModelsToRecopy = [
    'Contract.php',
    'Currency.php',
    'Invoice.php',
    'InvoiceItem.php',
    'Project.php',
    'Service.php',
    'ServiceCategory.php',
    'ServiceOrder.php',
    'ServicePackage.php',
    'TeamMember.php',
];

$monolithModelsDir = dirname(__DIR__) . '/musoftwares.com/app/Models';
$appModelsDir = __DIR__ . '/app/Models';

// Copy them from the monolith directly into Core/Models
foreach ($legacyModelsToRecopy as $file) {
    $source = $monolithModelsDir . '/' . $file;
    $dest = $coreModelsDir . '/' . $file;
    if (file_exists($source)) {
        copy($source, $dest);
        echo "Copied legacy $file to Core/Models\n";
    }
}

// 3. Move all remaining models from app/Models to Core/Models (except User.php, Traits, and the ones we deleted)
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($appModelsDir));
$movedModels = [];
foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $basename = $file->getFilename();
        if ($basename === 'User.php' || strpos($file->getRealPath(), DIRECTORY_SEPARATOR . 'Traits' . DIRECTORY_SEPARATOR) !== false) {
            continue;
        }

        $dest = $coreModelsDir . '/' . $basename;
        rename($file->getRealPath(), $dest);
        $movedModels[] = basename($basename, '.php');
        echo "Moved $basename to Core/Models\n";
    }
}

// Add the 10 models to the list of moved models for namespace updating
foreach ($legacyModelsToRecopy as $file) {
    $movedModels[] = basename($file, '.php');
}

// 4. Update namespaces in all these models to Modules\Core\Models
foreach ($movedModels as $modelName) {
    $path = $coreModelsDir . '/' . $modelName . '.php';
    if (file_exists($path)) {
        $content = file_get_contents($path);
        $content = preg_replace('/namespace\s+App\\\Models;/', 'namespace Modules\Core\Models;', $content);
        file_put_contents($path, $content);
        echo "Updated namespace for $modelName\n";
    }
}

// 5. Global find and replace to fix all references across the app
$directoriesToScan = [
    __DIR__ . '/app',
    __DIR__ . '/Modules',
    __DIR__ . '/routes',
];

$previousIncorrectMappings = [
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

function scanAndReplace($dir, $movedModels, $previousIncorrectMappings) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $path = $file->getRealPath();
            $content = file_get_contents($path);
            $newContent = $content;

            // First, fix the incorrect ones from the previous script (e.g. Modules\ERP\Models\Invoice -> Modules\Core\Models\Invoice)
            foreach ($previousIncorrectMappings as $modelName => $wrongModule) {
                $wrongNamespace = "Modules\\$wrongModule\\Models\\$modelName";
                $correctNamespace = "Modules\\Core\\Models\\$modelName";
                $newContent = str_replace($wrongNamespace, $correctNamespace, $newContent);
            }

            // Then, fix all the old App\Models references for all the moved models
            foreach ($movedModels as $modelName) {
                $oldNamespace = "App\\Models\\$modelName";
                $newNamespace = "Modules\\Core\\Models\\$modelName";
                
                // Avoid replacing App\Models\User
                if ($modelName !== 'User') {
                    $newContent = str_replace($oldNamespace, $newNamespace, $newContent);
                }
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
        scanAndReplace($dir, $movedModels, $previousIncorrectMappings);
    }
}

echo "Done.\n";
