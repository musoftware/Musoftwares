<?php

namespace Modules\GoldSavers\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiInsightService
{
    /**
     * Generate smart insights for a user's gold saving wallets using Google Gemini API.
     */
    public function generateInsights($wallets, $portfolioData, $latestPrice)
    {
        $keysString = \App\Models\AdminSettings::GetValue('gemini_api_keys');
        $keys = array_filter(array_map('trim', explode(',', $keysString ?? '')));
        
        $apiKey = count($keys) > 0 ? $keys[array_rand($keys)] : env('GEMINI_API_KEY');
        
        if (!$apiKey) {
            return $this->fallbackInsights();
        }

        $language = app()->getLocale() == 'ar' ? 'Arabic' : 'English';

        // Prompt formulation
        $prompt = "You are a financial advisor for a gold saving application. 
Analyze the user's gold wallets and provide exactly 3 brief, actionable, and encouraging insights or tips in {$language}. 
The user has the following portfolio: 
Total Grams: {$portfolioData['total_grams']}g
Current Value: {$portfolioData['current_value']}
Profit: {$portfolioData['total_profit']}
Live 21k Gold Price: " . ($latestPrice ? $latestPrice->price_gram_21k : 'N/A') . "

Wallets data:
";
        foreach ($wallets as $wallet) {
            $prompt .= "- Wallet '{$wallet->name}': {$wallet->balance_grams}g saved out of {$wallet->target_grams}g target.\n";
        }

        $prompt .= "\nFormat the response strictly as a JSON array of 3 objects, each with an 'icon' (must be exactly one of: 'TrendingUp', 'Target', 'Lightbulb') and 'text' (the short insight sentence). Do not include markdown code blocks. Example: [{\"icon\": \"TrendingUp\", \"text\": \"Insight text here\"}]";

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$apiKey}", [
                'contents' => [
                    ['parts' => [['text' => $prompt]]]
                ]
            ]);

            if ($response->successful()) {
                $result = $response->json();
                $text = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';
                
                // Clean markdown from response
                $text = trim(str_replace(['```json', '```'], '', $text));
                
                $insights = json_decode($text, true);
                
                if (is_array($insights) && count($insights) > 0) {
                    return $insights;
                }
            } else {
                Log::error('Gemini API Error', ['response' => $response->body()]);
            }
        } catch (\Exception $e) {
            Log::error('Gemini Service Exception', ['message' => $e->getMessage()]);
        }

        return $this->fallbackInsights();
    }

    private function fallbackInsights()
    {
        return [
            ['icon' => 'TrendingUp', 'text' => __('gold_saver.insight_gold_up') ?? 'Keep saving!'],
            ['icon' => 'Target', 'text' => __('gold_saver.insight_goal_near') ?? 'You are getting closer to your goals.'],
            ['icon' => 'Lightbulb', 'text' => __('gold_saver.insight_average_cost') ?? 'Monitor prices for best buying time.'],
        ];
    }
}
