<?php
$dir = __DIR__ . '/Modules/ERP/Http/Requests';

$files = [
    'StoreClientRequest.php',
    'StoreInvoiceRequest.php',
    'StoreProductRequest.php',
    'StoreProjectRequest.php',
    'StoreTaskRequest.php',
    'UpdateClientRequest.php',
    'UpdateProductRequest.php',
    'UpdateProjectRequest.php',
    'UpdateTaskRequest.php',
    'AdjustStockRequest.php',
    'UpdateInvoiceRequest.php'
];

foreach ($files as $file) {
    $path = $dir . DIRECTORY_SEPARATOR . $file;
    if (!file_exists($path)) continue;
    
    $content = file_get_contents($path);
    
    $pattern = '/public function authorize\(\)\s*\{\s*(.*?)\s*\}/s';
    $replacement = "public function authorize()\n    {\n        return true;\n    }";
    
    $newContent = preg_replace($pattern, $replacement, $content);
    if ($newContent !== $content) {
        file_put_contents($path, $newContent);
        echo "Reverted $file to return true;\n";
    }
}
