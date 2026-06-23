<?php

$dir = new RecursiveDirectoryIterator(__DIR__ . '/app/Http/Requests');
$ite = new RecursiveIteratorIterator($dir);
$files = new RegexIterator($ite, '/^.+\.php$/', RegexIterator::GET_MATCH);

$replacedCount = 0;

foreach ($files as $file) {
    $path = $file[0];
    
    // We only want to auto-replace in Admin requests or CRM/Booking requests where it's a backend operation
    if (strpos($path, 'Admin') !== false || strpos($path, 'CRM') !== false || strpos($path, 'Booking') !== false) {
        $content = file_get_contents($path);
        
        $pattern = '/return\s+true\s*;(\s*\/\/[^\n]*)?/';
        
        // Let's see if we should do role-based for Admin
        if (strpos($path, 'Admin') !== false) {
            $replacement = "return \$this->user() && \$this->user()->hasAnyRole(['admin', 'super_admin', 'Admin', 'superadmin']);$1";
            
            if (preg_match($pattern, $content)) {
                $newContent = preg_replace($pattern, $replacement, $content);
                file_put_contents($path, $newContent);
                echo "Replaced in: $path\n";
                $replacedCount++;
            }
        }
    }
}

echo "Replaced in $replacedCount files.\n";
