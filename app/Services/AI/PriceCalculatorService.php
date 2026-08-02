<?php

namespace App\Services\AI;

use App\Services\BaseService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PriceCalculatorService extends BaseService
{
    /**
     * Call OpenAI API to get a structured JSON estimate.
     */
    public function estimate(string $requirements): ?array
    {
        $apiKeysString = \App\Models\AdminSettings::GetValue('gemini_api_keys') 
            ?: \App\Models\AdminSettings::GetValue('gemini_api_key') 
            ?: config('services.gemini.key');

        if (!$apiKeysString) {
            Log::error('Gemini API keys missing in settings for PriceCalculatorService.');
            return null;
        }

        $keys = array_filter(array_map('trim', explode(',', $apiKeysString)));
        $apiKey = $keys[0] ?? null;

        if (!$apiKey) {
            return null;
        }

        $model = \App\Models\AdminSettings::GetValue('gemini_model', 'gemini-2.0-flash');

        $prompt = "You are an expert enterprise software estimator. The user has provided the following requirements for a freelance software project:\n\n"
                ."Requirements: {$requirements}\n\n"
                .'Provide a cost breakdown, an estimated timeline in weeks, and a total estimated cost (use a generic currency format or assume USD). '
                ."Return ONLY a valid JSON object with the following structure:\n"
                ."{\n"
                .'  "items": [ { "name": "Feature X", "cost": 500, "duration_days": 5 } ],'."\n"
                .'  "timeline_weeks": 4,'."\n"
                .'  "total_cost": 2500'."\n"
                .'}';

        try {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

            $response = Http::timeout(45)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->post($url, [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.2,
                        'responseMimeType' => 'application/json',
                    ],
                ]);

            if ($response->successful()) {
                $content = $response->json('candidates.0.content.parts.0.text');
                if ($content !== null) {
                    $content = preg_replace('/```json|```/', '', $content);
                    return json_decode(trim($content), true);
                }
            }

            Log::error('Gemini API error in PriceCalculatorService', ['response' => $response->body()]);
            return null;

        } catch (\Exception $e) {
            Log::error('Exception in PriceCalculatorService: '.$e->getMessage());
            return null;
        }
    }
}
