<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Modules\SmsPaymentGateway\Models\SmsPaymentGatewayDevice;
use Modules\SmsPaymentGateway\Models\SmsPaymentGatewayTransaction;
use App\Models\User;

try {
    DB::beginTransaction();
    
    // Create dummy user
    $user = User::factory()->create();
    
    $device = SmsPaymentGatewayDevice::create([
        'user_id' => $user->id,
        'device_name' => 'Test Device',
        'device_token' => 'test_device_token_123',
        'status' => 'connected',
    ]);

    $payload = [
        'sender' => 'WE Pay',
        'message' => 'تم استلام مبلغ 1500 EGP من رقم 01015218548',
        'timestamp' => 1717258380614,
        'device_token' => 'test_device_token_123',
        'is_test' => true
    ];
    
    $ingestionService = app(\Modules\SmsPaymentGateway\Services\TransactionIngestionService::class);
    $response = $ingestionService->ingestSms($device, $payload, 'WE Pay');
    
    echo "Response:\n";
    print_r($response);
    
    $transactions = SmsPaymentGatewayTransaction::where('device_id', $device->id)->get();
    echo "\nTransactions Created: " . $transactions->count() . "\n";
    if ($transactions->count() > 0) {
        print_r($transactions->first()->toArray());
    }
    
    DB::rollBack();
    echo "\nTest Passed Successfully!\n";
} catch (\Exception $e) {
    DB::rollBack();
    echo "\nException Thrown:\n";
    echo $e->getMessage() . "\n";
    echo $e->getFile() . " on line " . $e->getLine() . "\n";
}
