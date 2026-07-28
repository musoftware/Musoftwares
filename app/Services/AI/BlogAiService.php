<?php

namespace App\Services\AI;

use App\Models\AdminSettings;
use App\Services\BaseService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Marketplace\Models\Service;

class BlogAiService extends BaseService
{
    /**
     * Generate a new, unique blog article for a service.
     */
    public function generateArticleForService(Service $service, array $existingTitles = [], string $language = 'en'): ?array
    {
        $existingTopicsStr = empty($existingTitles)
            ? "None. This is the first article."
            : "- " . implode("\n- ", $existingTitles);

        $prompt = "You are a professional technical copywriter, digital marketer, and SEO expert.
Your goal is to write a highly engaging, informative, and SEO-optimized blog article that relates to a specific service offered on our platform. The article must naturally lead the reader to want to learn more or purchase this service.

Service Offered on our Platform:
- Title: {$service->title}
- Tagline: {$service->tagline}
- Description: {$service->description}

Language: Please write the entire article in the language matching this code: \"{$language}\" (e.g. 'en' for English, 'ar' for Arabic).

CRITICAL REQUIREMENT - AVOID DUPLICATION:
The following articles have already been written for this service:
{$existingTopicsStr}

You MUST generate a NEW, distinct topic that is relevant to the service but does NOT duplicate, repeat, or significantly overlap with the topics listed above. Try to focus on a different angle, target audience, use case, tutorial, troubleshooting guide, comparison, or industry trend.

ARTICLE STRUCTURE REQUIREMENTS:
1. Title: Catchy, SEO-friendly, and professional (5-12 words).
2. Excerpt: A brief, compelling summary of the article (1-3 sentences) to attract clicks.
3. Content: The full article body written in rich Markdown format. It should be comprehensive (around 600-1000 words), detailed, and structured with H2/H3 headings, bullet points, and paragraphs. Make sure the flow leads the reader towards our service naturally at the end.
4. Meta Title: An optimized title tag for search engines (under 60 characters).
5. Meta Description: A compelling meta description for search engines (under 160 characters).
6. Featured Image Prompt: A vivid visual description (for DALL-E) to generate a professional cover image for this blog post.

You MUST return strictly valid JSON matching this exact structure without markdown code block ticks:
{
  \"title\": \"Generated Article Title\",
  \"excerpt\": \"Short summary of the article...\",
  \"content\": \"# Heading 1\\n\\nIntroduction paragraph...\\n\\n## Subheading...\\n\\nDetailed content here...\\n\\n### Key Benefits...\\n- Benefit 1\\n- Benefit 2\\n\\n## Conclusion\\nConcluding remarks naturally referring to our '{$service->title}' service for professional assistance.\",
  \"meta_title\": \"SEO Meta Title\",
  \"meta_description\": \"SEO Meta Description\",
  \"image_prompt\": \"Vivid DALL-E prompt for the cover image (e.g. A modern studio flat lay showing...)\"
}";

        return $this->callLLM($prompt);
    }

    /**
     * Central LLM Router that calls Gemini (preferred) or OpenAI.
     */
    private function callLLM(string $prompt): ?array
    {
        // Try Gemini First
        $geminiKeys = AdminSettings::GetValue('gemini_api_keys') ?: AdminSettings::GetValue('gemini_api_key') ?: config('services.gemini.key');
        if ($geminiKeys) {
            $keys = array_filter(array_map('trim', explode(',', $geminiKeys)));
            $apiKey = $keys[0] ?? null;
            if ($apiKey) {
                return $this->callGemini($prompt, $apiKey);
            }
        }

        // Fallback to OpenAI
        $openAiKey = AdminSettings::GetValue('openai_api_key', config('services.openai.key'));
        if ($openAiKey) {
            return $this->callOpenAI($prompt, $openAiKey);
        }

        Log::error('No LLM credentials found (Gemini or OpenAI) in settings or configuration.');
        return null;
    }

    /**
     * Call Gemini API.
     */
    private function callGemini(string $prompt, string $apiKey): ?array
    {
        $model = AdminSettings::GetValue('gemini_model', 'gemini-2.0-flash');

        try {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

            $response = Http::timeout(60)
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
                        'temperature' => 0.7,
                        'responseMimeType' => 'application/json',
                    ],
                ]);

            if ($response->successful()) {
                $content = $response->json('candidates.0.content.parts.0.text');
                return json_decode($content, true);
            }

            Log::error('Gemini API error in BlogAiService', ['response' => $response->body()]);
            return null;
        } catch (\Exception $e) {
            Log::error('Exception in BlogAiService callGemini: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Call OpenAI API.
     */
    private function callOpenAI(string $prompt, string $apiKey): ?array
    {
        $model = AdminSettings::GetValue('openai_model') ?: 'gpt-4o-mini';

        try {
            $response = Http::timeout(60)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . trim($apiKey),
                    'Content-Type'  => 'application/json',
                ])
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $model,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        ['role' => 'system', 'content' => 'You respond strictly in valid JSON format matching the requested schema.'],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'temperature' => 0.7,
                ]);

            if ($response->successful()) {
                $content = $response->json('choices.0.message.content');
                return json_decode($content, true);
            }

            Log::error('OpenAI API error in BlogAiService', ['response' => $response->body()]);
            return null;
        } catch (\Exception $e) {
            Log::error('Exception in BlogAiService callOpenAI: ' . $e->getMessage());
            return null;
        }
    }
}
