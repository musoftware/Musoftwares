<?php

$files = [
    "Modules/Booking/tests/Feature/WaConfirm/BookingCustomerConfirmationFlowTest.php",
    "Modules/Booking/tests/Feature/WaConfirm/ConfirmationTokenManagerTest.php",
    "Modules/Booking/tests/Feature/WaConfirm/WhatsAppConfirmationActionProcessorTest.php",
];

foreach ($files as $filePath) {
    if (file_exists($filePath)) {
        $content = file_get_contents($filePath);
        
        // Add Schema namespace if not exists
        if (strpos($content, "use Illuminate\Support\Facades\Schema;") === false) {
            $content = str_replace("use Tests\TestCase;", "use Tests\TestCase;\nuse Illuminate\Support\Facades\Schema;", $content);
        }

        // Add setUp method if it doesn't exist, or modify it if it does
        if (strpos($content, "protected function setUp(): void") === false) {
            $setUpMethod = "\n    protected function setUp(): void\n    {\n        parent::setUp();\n        Schema::disableForeignKeyConstraints();\n    }\n";
            $content = preg_replace('/class [a-zA-Z0-9_]+ extends TestCase\s*\{(\s*use [a-zA-Z0-9_\\\\]+;)?/', "$0\n$setUpMethod", $content);
        } else {
            $content = str_replace("parent::setUp();", "parent::setUp();\n        Schema::disableForeignKeyConstraints();", $content);
        }

        file_put_contents($filePath, $content);
        echo "Updated: " . $filePath . "\n";
    }
}
