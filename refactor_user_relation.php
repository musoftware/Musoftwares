<?php

function processDirectory($dir) {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($files as $file) {
        if ($file->isDir() || $file->getExtension() !== 'php') continue;
        $path = $file->getPathname();
        
        $content = file_get_contents($path);
        $original = $content;

        // In ERPDashboardController, we leave Onboarding methods intact.
        // We will skip ERPDashboardController and handle it manually.
        if (strpos($path, 'ERPDashboardController.php') !== false) {
            continue;
        }

        $content = str_replace(
            "\$user = auth('erp_team')->user()?->tenant?->user;", 
            "\$teamMember = auth('erp_team')->user();\n            \$user = \$teamMember?->tenant?->user;", 
            $content
        );

        if ($content !== $original) {
            file_put_contents($path, $content);
            echo "Updated $path\n";
        }
    }
}

processDirectory('Modules/ERP/Http/Controllers');
processDirectory('Modules/ERP/app/Features');
echo "Done.\n";
