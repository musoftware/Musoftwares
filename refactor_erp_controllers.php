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

        // Replace Auth::id() and auth()->id() -> auth('erp_team')->id()
        $content = preg_replace('/(?<!::)Auth::id\(\)/', "auth('erp_team')->id()", $content);
        $content = preg_replace('/auth\(\)->id\(\)/', "auth('erp_team')->id()", $content);
        
        // Replace Auth::user() and auth()->user() -> auth('erp_team')->user()
        $content = preg_replace('/(?<!::)Auth::user\(\)/', "auth('erp_team')->user()", $content);
        $content = preg_replace('/auth\(\)->user\(\)/', "auth('erp_team')->user()", $content);

        // Replace Tenant::where('user_id', auth('erp_team')->id())->firstOrFail() -> auth('erp_team')->user()->tenant
        $content = preg_replace('/\\\\?Modules\\\\ERP\\\\Models\\\\Tenant::where\(\'user_id\',\s*.*?\)->firstOrFail\(\)/', "auth('erp_team')->user()->tenant", $content);
        $content = preg_replace('/\\\\?Modules\\\\ERP\\\\Models\\\\Tenant::where\(\'user_id\',\s*.*?\)->first\(\)/', "auth('erp_team')->user()->tenant", $content);
        
        $content = preg_replace('/Tenant::where\(\'user_id\',\s*.*?\)->firstOrFail\(\)/', "auth('erp_team')->user()->tenant", $content);
        $content = preg_replace('/Tenant::where\(\'user_id\',\s*.*?\)->first\(\)/', "auth('erp_team')->user()->tenant", $content);
        $content = preg_replace('/Tenant::where\(\'user_id\',\s*.*?\)->value\(\'id\'\)/', "auth('erp_team')->user()->tenant_id", $content);

        if ($content !== $original) {
            file_put_contents($path, $content);
            echo "Updated $path\n";
        }
    }
}

processDirectory('Modules/ERP/Http/Controllers');
processDirectory('Modules/ERP/app/Features');
echo "Done.\n";
