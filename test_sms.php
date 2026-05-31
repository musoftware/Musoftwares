<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Modules\SmsPaymentGateway\Services\DeterministicSmsParser;
use Modules\SmsPaymentGateway\Models\SmsPaymentGatewayDevice;

echo "Running SMS Parser Tests...\n\n";

$parser = app(DeterministicSmsParser::class);

$testCases = [
    [
        'message' => 'تم استلام مبلغ 120.50 ج.م من 01012345678. رصيدك الحالي هو 5000 ج.م.',
        'expected' => [
            'amount' => 120.50,
            'phone' => '01012345678',
            'type' => 'received',
            'is_valid' => true
        ]
    ],
    // Add real messages based on existing logs or examples if needed
    [
        'message' => 'تم تحويل 500 ج.م لرقم 01111111111. مصاريف التحويل 1 ج.م.',
        'expected' => [
            'amount' => 500.00,
            'phone' => '01111111111',
            'type' => 'sent',
            'is_valid' => true
        ]
    ]
];

// Let's actually just try to run the DeterministicSmsParserTest manually if we can!
$testFilePath = __DIR__.'/Modules/SmsPaymentGateway/tests/Unit/DeterministicSmsParserTest.php';
if (file_exists($testFilePath)) {
    echo "Found DeterministicSmsParserTest.php. Running methods manually...\n";
    require_once $testFilePath;
    // We can't easily instantiate it because it extends PHPUnit\Framework\TestCase which might throw the same error.
    // Instead, let's just parse the cases defined there if possible.
}

echo "Manual parsing test:\n";
foreach ($testCases as $index => $case) {
    echo "Case " . ($index + 1) . ":\n";
    echo "Message: " . $case['message'] . "\n";
    $result = $parser->parse('Vodafone', $case['message']);
    
    echo "Result:\n";
    print_r($result);
    echo "---------------------------\n";
}

echo "Done.\n";
