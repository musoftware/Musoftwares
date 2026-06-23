<?php
$files = glob("app/Policies/*.php");
$files = array_merge($files, glob("Modules/PasswordSync/app/Policies/*.php"));

foreach ($files as $file) {
    $content = file_get_contents($file);
    if (strpos($content, "return false;") === false) continue;
    
    // Add before method
    $beforeMethod = "
    public function before(User \$user, string \$ability): ?bool
    {
        if (\$user->hasRole(['super_admin', 'admin', 'superadmin', 'Admin'])) {
            return true;
        }
        return null;
    }
";
    if (strpos($content, "function before(") === false) {
        $content = preg_replace("/class \w+\n\{/", "$0\n$beforeMethod", $content);
    }
    
    // Replace viewAny and create to return true
    $content = preg_replace("/(public function viewAny.*?)\{\n\s*return false;\n\s*\}/s", "$1{\n        return true;\n    }", $content);
    $content = preg_replace("/(public function create.*?)\{\n\s*return false;\n\s*\}/s", "$1{\n        return true;\n    }", $content);

    // For other methods, replace return false with check user_id if the model variable is present
    $content = preg_replace_callback("/public function \w+\(User \\\$user, (.*?) \\\$(\w+)\).*?\{\n\s*return false;\n\s*\}/s", function($matches) {
        $modelClass = $matches[1];
        $modelVar = $matches[2];
        
        // check if user_id or similar exists, but since we can't reflect safely here, we just use a generic check
        // We'll write code that checks if the property exists or assume it does
        return str_replace("return false;", "return isset(\$$modelVar->user_id) ? \$user->id === \$$modelVar->user_id : false;", $matches[0]);
    }, $content);
    
    file_put_contents($file, $content);
    echo "Updated $file\n";
}
