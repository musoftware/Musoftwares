<?php

namespace App\Services\AI;

use App\Models\AdminSettings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiEstimatorService
{
    /**
     * Call Gemini API to get an estimated number of hours.
     */
    public function estimateHours(string $taskDescription): ?float
    {
        $apiKeysString = AdminSettings::GetValue('gemini_api_keys');
        
        if (!$apiKeysString) {
            Log::error('Gemini API keys missing in settings for AI Estimator.');
            return null;
        }

        // Split comma-separated keys and pick the first valid one
        $keys = array_filter(array_map('trim', explode(',', $apiKeysString)));
        if (empty($keys)) {
            Log::error('Gemini API keys empty in settings for AI Estimator.');
            return null;
        }
        
        // Simple rotation or just take the first key for now
        $apiKey = $keys[0];

        $prompt = "You are an expert enterprise software estimator. The user has provided the following requirements for a task:\n\n"
                . "Task Description: {$taskDescription}\n\n"
                . "Estimate the total number of hours of work required for this task. "
                . "Reply ONLY with the integer or decimal number of hours, nothing else. "
                . "For example, if you think it takes 5 hours, reply with '5'. If 2.5 hours, reply with '2.5'.";

        try {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$apiKey}";
            
            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->post($url, [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.2, // Low temperature for more deterministic/consistent estimates
                    ]
                ]);

            if ($response->successful()) {
                $content = $response->json('candidates.0.content.parts.0.text');
                
                if ($content !== null) {
                    // Extract just the number in case Gemini adds extra text despite the prompt
                    if (preg_match('/([0-9]*\.?[0-9]+)/', $content, $matches)) {
                        return (float) $matches[1];
                    }
                }
            }

            Log::error('Gemini API error in GeminiEstimatorService', ['response' => $response->body()]);
            return null;

        } catch (\Exception $e) {
            Log::error('Exception in GeminiEstimatorService: ' . $e->getMessage());
            return null;
        }
    }
}
