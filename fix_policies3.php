<?php
$files = glob("app/Policies/*.php");
$files = array_merge($files, glob("Modules/PasswordSync/app/Policies/*.php"));

foreach ($files as $file) {
    $content = file_get_contents($file);
    $content = preg_replace_callback("/public function (\w+)\(User \\\$user, ([a-zA-Z0-9_]+) \\\$([a-zA-Z0-9_]+)\)(.*?)\{\n\s*return isset\(\\\$[a-zA-Z0-9_]+->user_id\)(.*?)\;\n\s*\}/s", function($matches) {
        $method = $matches[1];
        $modelClass = $matches[2];
        $modelVar = $matches[3];
        $signature = $matches[4];
        
        $body = "return isset(\${$modelVar}->user_id) ? \$user->id === \${$modelVar}->user_id : false;";
        return "public function $method(User \$user, $modelClass \$$modelVar)$signature{\n        $body\n    }";
    }, $content);
    
    file_put_contents($file, $content);
    echo "Fixed $file\n";
}
