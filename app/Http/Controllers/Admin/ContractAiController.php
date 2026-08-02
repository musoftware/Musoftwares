<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminSettings;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class ContractAiController extends Controller
{
    public function generate(Request $request)
    {
        $request->validate([
            'project_id' => 'required|integer',
            'prompt' => 'required|string',
        ]);

        $project = Project::findOrFail($request->project_id);

        $user = Auth::user();
        $defaultProvider = 'gemini';
        $apiKeysString = AdminSettings::GetValue('gemini_api_keys') ?: AdminSettings::GetValue('gemini_api_key') ?: env('GEMINI_API_KEY') ?: config('services.gemini.key');
        
        $apiKey = null;
        if ($apiKeysString) {
            $keys = array_filter(array_map('trim', explode(',', $apiKeysString)));
            $apiKey = $keys[0] ?? null;
        }
        
        $model = AdminSettings::GetValue('gemini_model', 'gemini-2.0-flash');

        if (empty($apiKey)) {
            return response()->json([
                'error' => "Please set your Gemini API key in admin settings.",
            ], 400);
        }

        try {
            $prompt = "You are an expert sales and legal consultant for a software company.
            Project Name: {$project->project_name}
            Client Needs / User Prompt: {$request->prompt}
            Context: Generating a professional proposal/contract based on the user prompt.

            Please generate professional content for the following fields:
            description, payment_terms, terms, notes, duration, key_features, pricing_items, total_amount

            Guidelines:
            - description: Executive summary of the project.
            - payment_terms: Detailed milestone payment structure.
            - terms: Standard liability, confidentiality, and IP clauses (brief).
            - notes: Polite closing notes and validity period.
            - duration: Estimated time to complete (e.g., '2 Weeks', '30 Days').
            - key_features: An array of string features or deliverables for this project.
            - pricing_items: An array of objects for the breakdown. Each object must have: 
              'item' (title), 'description' (short), 'hours' (integer estimate), 'hourly_rate' (integer), 'total' (integer).
            - total_amount: The sum of all pricing item totals (integer).

            Return ONLY valid JSON in this format:
            {
                \"description\": \"...\",
                \"payment_terms\": \"...\",
                \"terms\": \"...\",
                \"notes\": \"...\",
                \"duration\": \"...\",
                \"key_features\": [\"Feature 1\", \"Feature 2\"],
                \"pricing_items\": [{\"item\": \"...\", \"description\": \"...\", \"hours\": 10, \"hourly_rate\": 50, \"total\": 500}],
                \"total_amount\": 500
            }
            ";

            if ($defaultProvider === 'openai') {
                $response = Http::timeout(60)->withHeaders([
                    'Authorization' => 'Bearer '.$apiKey,
                    'Content-Type' => 'application/json',
                ])->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'system', 'content' => 'You are a helpful assistant that outputs JSON.'],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'temperature' => 0.7,
                ]);
                $content = $response->json()['choices'][0]['message']['content'] ?? '';
            } else {
                $response = Http::timeout(60)->withHeaders([
                    'Content-Type' => 'application/json',
                ])->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}", [
                    'contents' => [
                        ['role' => 'user', 'parts' => [['text' => $prompt]]],
                    ],
                ]);
                $content = $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? '';
            }

            // Cleanup JSON
            $cleanContent = preg_replace('/```json\s*/', '', $content);
            $cleanContent = preg_replace('/```\s*/', '', $cleanContent);
            $json = json_decode(trim($cleanContent), true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                return response()->json($json); // Return raw JSON directly as modal expects res.data.description
            } else {
                return response()->json(['error' => 'AI response format error.'], 500);
            }

        } catch (\Exception $e) {
            return response()->json(['error' => 'AI Generation Failed: '.$e->getMessage()], 500);
        }
    }

    public function review(Request $request)
    {
        $request->validate([
            'description' => 'required|string|min:50',
        ]);

        $user = Auth::user();
        $defaultProvider = 'gemini';
        $apiKeysString = AdminSettings::GetValue('gemini_api_keys') ?: AdminSettings::GetValue('gemini_api_key') ?: env('GEMINI_API_KEY') ?: config('services.gemini.key');
        
        $apiKey = null;
        if ($apiKeysString) {
            $keys = array_filter(array_map('trim', explode(',', $apiKeysString)));
            $apiKey = $keys[0] ?? null;
        }
        
        $model = AdminSettings::GetValue('gemini_model', 'gemini-2.0-flash');
        $providerName = 'Gemini';

        if (empty($apiKey)) {
            return response()->json([
                'error' => "Please set your Gemini API key in admin settings.",
            ], 400);
        }

        try {
            $prompt = "You are an expert legal and business consultant. Review the following software development contract/proposal content for any issues, ambiguities, or missing critical clauses.

            Key areas to check:
            1. Clear scope of work.
            2. Payment terms clarity.
            3. Timeline and deliverables.
            4. Absence of 'hosting/maintenance' unless explicitly priced as recurring.

            Contract Content:
            {$request->description}

            Return a JSON response with:
            {
               \"critical_issues\": [\"Issue 1\", \"Issue 2\"],
               \"suggestions\": [\"Suggestion 1\", \"Suggestion 2\"],
               \"refined_content\": \"The full contract content with improvements applied (keep markdown format).\"
            }
            ";

            if ($defaultProvider === 'openai') {
                $response = Http::timeout(120)->withHeaders([
                    'Authorization' => 'Bearer '.$apiKey,
                    'Content-Type' => 'application/json',
                ])->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'system', 'content' => 'You are a helpful contract reviewer that outputs JSON.'],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'temperature' => 0.5,
                ]);
                $content = $response->json()['choices'][0]['message']['content'] ?? '';
            } else {
                $response = Http::timeout(120)->withHeaders([
                    'Content-Type' => 'application/json',
                ])->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}", [
                    'contents' => [
                        ['role' => 'user', 'parts' => [['text' => $prompt]]],
                    ],
                ]);
                $content = $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? '';
            }

            // Parse JSON
            $cleanContent = preg_replace('/```json\s*/', '', $content);
            $cleanContent = preg_replace('/```\s*/', '', $cleanContent);
            $json = json_decode(trim($cleanContent), true);

            if (json_last_error() === JSON_ERROR_NONE && ! empty($json['refined_content'])) {
                return response()->json(['data' => $json]);
            } else {
                return response()->json(['error' => 'AI could not process the review correctly.'], 500);
            }

        } catch (\Exception $e) {
            return response()->json(['error' => 'AI Review Failed: '.$e->getMessage()], 500);
        }
    }
}
