<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\AdminSettings;
use Illuminate\Support\Facades\Http;

echo "=== Gemini Direct API Test (with SSL verify bypass for local dev) ===\n\n";

$key = 'AIzaSyBbfp8erxNThtKPBMss581GAWfKO0AlS1g';
$model = 'gemini-2.0-flash';
$url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$key}";

$response = Http::withoutVerifying()
    ->timeout(15)
    ->withHeaders(['Content-Type' => 'application/json'])
    ->post($url, [
        'contents' => [
            ['parts' => [['text' => 'You are an AI software estimator. Estimate a mobile app in JSON: {"total_cost": 2500, "timeline_weeks": 4}']]],
        ],
        'generationConfig' => [
            'temperature' => 0.2,
            'responseMimeType' => 'application/json',
        ],
    ]);

echo "Status Code: " . $response->status() . "\n";
echo "Response Body:\n" . $response->body() . "\n";
