<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PriceCalculatorService
{
    /**
     * Call OpenAI API to get a structured JSON estimate.
     */
    public function estimate(string $requirements): ?array
    {
        $apiKey = env('OPENAI_API_KEY');
        
        if (!$apiKey) {
            Log::error('OpenAI API key missing in PriceCalculatorService.');
            return null;
        }

        $prompt = "You are an expert enterprise software estimator. The user has provided the following requirements for a freelance software project:\n\n"
                . "Requirements: {$requirements}\n\n"
                . "Provide a cost breakdown, an estimated timeline in weeks, and a total estimated cost (use a generic currency format or assume USD). "
                . "Return ONLY a valid JSON object with the following structure:\n"
                . "{\n"
                . '  "items": [ { "name": "Feature X", "cost": 500, "duration_days": 5 } ],' . "\n"
                . '  "timeline_weeks": 4,' . "\n"
                . '  "total_cost": 2500' . "\n"
                . "}";

        try {
            $response = Http::withToken($apiKey)
                ->timeout(30)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-3.5-turbo', // Using 3.5 turbo for cost efficiency
                    'messages' => [
                        ['role' => 'system', 'content' => 'You are an expert software estimator. Output strictly valid JSON.'],
                        ['role' => 'user', 'content' => $prompt]
                    ],
                    'temperature' => 0.2,
                ]);

            if ($response->successful()) {
                $content = $response->json('choices.0.message.content');
                // Remove markdown code blocks if any
                $content = preg_replace('/```json|```/', '', $content);
                return json_decode(trim($content), true);
            }

            Log::error('OpenAI API error in PriceCalculatorService', ['response' => $response->body()]);
            return null;

        } catch (\Exception $e) {
            Log::error('Exception in PriceCalculatorService: ' . $e->getMessage());
            return null;
        }
    }
}
