<?php
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('Modules/Booking/app/Features'));
foreach ($files as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        $modified = false;
        if (strpos($content, 'namespace Modules\Booking\Features') !== false) {
            $content = str_replace('namespace Modules\Booking\Features', 'namespace Modules\Booking\app\Features', $content);
            $modified = true;
        }
        if (strpos($content, 'use Modules\Booking\Features') !== false) {
            $content = str_replace('use Modules\Booking\Features', 'use Modules\Booking\app\Features', $content);
            $modified = true;
        }
        if ($modified) {
            file_put_contents($file->getPathname(), $content);
            echo "Fixed: " . $file->getPathname() . "\n";
        }
    }
}
$testFiles = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('Modules/Booking/tests'));
foreach ($testFiles as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        if (strpos($content, 'use Modules\Booking\Features') !== false) {
            $content = str_replace('use Modules\Booking\Features', 'use Modules\Booking\app\Features', $content);
            file_put_contents($file->getPathname(), $content);
            echo "Fixed tests: " . $file->getPathname() . "\n";
        }
    }
}
echo "Done.\n";
