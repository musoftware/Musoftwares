<?php

namespace App\Services\AI;

use App\Models\AdminSettings;
use App\Services\BaseService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MarketplaceAiService extends BaseService
{
    /**
     * Generate complete Marketplace Service payload + AI Cover Image
     */
    public function generateServiceData(string $titlePrompt, string $provider = 'chatgpt', array $categories = [], int $sellerId = 1): array
    {
        $categoriesFormatted = array_map(function ($c) {
            return [
                'id'   => $c['id'] ?? $c->id,
                'name' => $c['name'] ?? $c->name,
                'slug' => $c['slug'] ?? $c->slug,
            ];
        }, $categories);

        $prompt = $this->buildPrompt($titlePrompt, $categoriesFormatted);

        if (strtolower($provider) === 'gemini') {
            $aiResult = $this->callGemini($prompt);
        } else {
            $aiResult = $this->callOpenAI($prompt);
        }

        if (!$aiResult || empty($aiResult['title'])) {
            throw new \Exception('Failed to generate service details from AI. Please check your API key configuration.');
        }

        // Default fallback category if AI returned invalid or missing category_id
        $validCategoryIds = array_column($categoriesFormatted, 'id');
        if (!in_array($aiResult['category_id'] ?? null, $validCategoryIds)) {
            $aiResult['category_id'] = $categoriesFormatted[0]['id'] ?? 1;
        }

        // Generate AI Cover Image
        $imagePrompt = $aiResult['image_prompt'] ?? ($aiResult['title'] . ' ' . ($aiResult['tagline'] ?? ''));
        $imageResult = $this->generateCoverImage($imagePrompt, $sellerId);

        $aiResult['gallery'] = $imageResult['gallery'];
        $aiResult['thumbnail'] = $imageResult['thumbnail'];

        return $aiResult;
    }

    private function buildPrompt(string $titlePrompt, array $categories): string
    {
        $categoriesJson = json_encode($categories, JSON_UNESCAPED_UNICODE);

        return <<<PROMPT
You are an expert digital agency marketer creating high-converting listings for a digital service marketplace.
The user provided the following service topic/title: "{$titlePrompt}".

Available Category List (Pick the single best category_id matching this service):
{$categoriesJson}

DESCRIPTION FORMATTING REQUIREMENTS:
The "description" field MUST be formatted professionally following this exact structure with clear section breaks:
1. An engaging hook question or headline (e.g. "Need to integrate real-time notifications into your users?").
2. An introductory value proposition paragraph explaining what will be built and the core benefits.
3. A scenario paragraph detailing use cases (e.g. "Whether you need announcements, order updates, reminders, marketing campaigns...").
4. A "What's Included" section with a bulleted list of 6-10 specific features/deliverables.
5. A "Technologies" section with a bulleted list of relevant programming languages, frameworks, APIs, and databases.

You MUST return strictly raw valid JSON matching this exact structure without markdown backticks:
{
  "title": "Professional Catchy Title (5-10 words)",
  "tagline": "Compelling short tagline highlighting key benefit (1 sentence)",
  "category_id": 1,
  "tags": ["tag1", "tag2", "tag3", "tag4"],
  "description": "Engaging hook question?\n\nClear introduction paragraph detailing exact integration and value proposition.\n\nWhether you need use case 1, use case 2, or use case 3, I will configure everything correctly and ensure seamless operation.\n\nWhat's Included\n- Deliverable 1\n- Deliverable 2\n- Deliverable 3\n- Deliverable 4\n- Deliverable 5\n- Clean, maintainable code\n\nTechnologies\n- Technology 1\n- Technology 2\n- Technology 3",
  "faq": [
    {"question": "What is included in the service?", "answer": "Detailed answer explaining deliverables..."},
    {"question": "How long does delivery take?", "answer": "Detailed answer explaining timeline..."}
  ],
  "requirements": [
    "Requirement 1 from client (e.g. access credentials, specifications)",
    "Requirement 2 from client"
  ],
  "packages": [
    {
      "name": "Basic",
      "description": "Essential starter package deliverable description.",
      "price": 25,
      "currency_id": 1,
      "delivery_days": 2,
      "revisions": 2,
      "features": ["Feature 1", "Feature 2"]
    },
    {
      "name": "Standard",
      "description": "Most popular complete package deliverable description.",
      "price": 75,
      "currency_id": 1,
      "delivery_days": 4,
      "revisions": 5,
      "features": ["Feature 1", "Feature 2", "Feature 3", "Feature 4"]
    },
    {
      "name": "Premium",
      "description": "Ultimate enterprise-grade package deliverable description.",
      "price": 150,
      "currency_id": 1,
      "delivery_days": 7,
      "revisions": -1,
      "features": ["Everything in Standard", "Priority Support", "Source Code", "Custom Addons"]
    }
  ],
  "image_prompt": "A professional software feature showcase grid collage presentation for {$titlePrompt}. Clean light backdrop displaying multiple realistic SaaS web app UI dashboards, admin control screens with analytics graphs, browser push notification popup dialogs, numbered badge headers (1, 2, 3), and a clean system architecture diagram. Modern, crisp, ultra-high resolution software presentation mockup."
}
PROMPT;
    }

    private function callOpenAI(string $prompt): ?array
    {
        $apiKey = AdminSettings::GetValue('openai_api_key', config('services.openai.key'));

        if (!$apiKey) {
            Log::error('OpenAI API key missing in settings.');
            return null;
        }

        $model = AdminSettings::GetValue('openai_model') ?: 'gpt-4o';

        try {
            $response = Http::timeout(45)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . trim($apiKey),
                    'Content-Type'  => 'application/json',
                ])
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $model,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        ['role' => 'system', 'content' => 'You respond strictly in valid JSON format.'],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'temperature' => 0.7,
                ]);

            if ($response->successful()) {
                $content = $response->json('choices.0.message.content');
                return json_decode($content, true);
            }

            Log::error('OpenAI API error in MarketplaceAiService', ['response' => $response->body()]);
            return null;
        } catch (\Exception $e) {
            Log::error('Exception in MarketplaceAiService callOpenAI: ' . $e->getMessage());
            return null;
        }
    }

    private function callGemini(string $prompt): ?array
    {
        $apiKeysString = AdminSettings::GetValue('gemini_api_keys') ?: AdminSettings::GetValue('gemini_api_key') ?: config('services.gemini.key');

        if (!$apiKeysString) {
            Log::error('Gemini API key missing in settings.');
            return null;
        }

        $keys = array_filter(array_map('trim', explode(',', $apiKeysString)));
        $apiKey = $keys[0] ?? null;

        if (!$apiKey) {
            return null;
        }

        $model = AdminSettings::GetValue('gemini_model', 'gemini-2.0-flash');

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
                        'temperature' => 0.7,
                        'responseMimeType' => 'application/json',
                    ],
                ]);

            if ($response->successful()) {
                $content = $response->json('candidates.0.content.parts.0.text');
                return json_decode($content, true);
            }

            Log::error('Gemini API error in MarketplaceAiService', ['response' => $response->body()]);
            return null;
        } catch (\Exception $e) {
            Log::error('Exception in MarketplaceAiService callGemini: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate Cover Image via latest OpenAI ChatGPT Image API (gpt-image-2 / dall-e-3) with Pollinations fallback
     */
    public function generateCoverImage(string $imagePrompt, int $sellerId): array
    {
        $apiKey = AdminSettings::GetValue('openai_api_key', config('services.openai.key'));
        $imageBinary = null;

        $uiCollagePrompt = "A professional software feature showcase grid collage presentation for " . Str::limit($imagePrompt, 300) . ". Clean light backdrop displaying multiple realistic SaaS web app UI dashboards, admin control screens with analytics graphs, browser push notification popup dialogs, numbered badge headers (1, 2, 3), and a clean system architecture diagram. Modern, crisp, ultra-high resolution software presentation mockup.";

        if ($apiKey) {
            $primaryModel = AdminSettings::GetValue('openai_image_model', 'gpt-image-2');
            $modelsToTry = array_unique(['gpt-image-2', 'dall-e-3']);

            foreach ($modelsToTry as $model) {
                try {
                    $payload = [
                        'model'           => $model,
                        'prompt'          => $uiCollagePrompt,
                        'n'               => 1,
                        'size'            => '1792x1024',
                        'response_format' => 'url',
                    ];

                    if (in_array($model, ['gpt-image-2', 'dall-e-3'])) {
                        $payload['quality'] = 'hd';
                        $payload['style']   = 'vivid';
                    }

                    $response = Http::timeout(65)
                        ->withHeaders([
                            'Authorization' => 'Bearer ' . trim($apiKey),
                            'Content-Type'  => 'application/json',
                        ])
                        ->post('https://api.openai.com/v1/images/generations', $payload);

                    if ($response->successful()) {
                        $imageUrl = $response->json('data.0.url');
                        if ($imageUrl) {
                            $imageBinary = Http::timeout(30)->get($imageUrl)->body();
                            if (!empty($imageBinary)) {
                                Log::info("Successfully generated cover image using OpenAI model: {$model}");
                                break;
                            }
                        }
                    } else {
                        Log::warning("OpenAI image generation failed with model {$model}, trying next fallback...", ['response' => $response->body()]);
                        
                        // Try 1024x1024 standard resolution for model if widescreen failed
                        $fallbackPayload = [
                            'model'           => $model,
                            'prompt'          => Str::limit($uiCollagePrompt, 400),
                            'n'               => 1,
                            'size'            => '1024x1024',
                            'response_format' => 'url',
                        ];
                        $response2 = Http::timeout(50)
                            ->withHeaders([
                                'Authorization' => 'Bearer ' . trim($apiKey),
                                'Content-Type'  => 'application/json',
                            ])
                            ->post('https://api.openai.com/v1/images/generations', $fallbackPayload);

                        if ($response2->successful()) {
                            $imageUrl = $response2->json('data.0.url');
                            if ($imageUrl) {
                                $imageBinary = Http::timeout(30)->get($imageUrl)->body();
                                if (!empty($imageBinary)) {
                                    Log::info("Successfully generated cover image (1024x1024) using OpenAI model: {$model}");
                                    break;
                                }
                            }
                        }
                    }
                } catch (\Exception $e) {
                    Log::error("OpenAI image generation exception with model {$model}: " . $e->getMessage());
                }
            }
        }

        // Fallback to Pollinations Flux AI model if OpenAI failed or key is missing
        if (!$imageBinary) {
            try {
                $pollinationsUrl = 'https://image.pollinations.ai/prompt/' . urlencode($uiCollagePrompt) . '?width=1200&height=675&model=flux&nologo=true&seed=' . rand(100, 9999);
                $res = Http::timeout(30)->get($pollinationsUrl);
                if ($res->successful()) {
                    $imageBinary = $res->body();
                }
            } catch (\Exception $e) {
                Log::error('Pollinations Flux fallback image generation failed: ' . $e->getMessage());
            }
        }

        if (!$imageBinary) {
            return ['gallery' => [], 'thumbnail' => null];
        }

        // Save image to public uploads disk
        $filename = 'ai_' . time() . '_' . Str::random(8) . '.jpg';
        $relativeFolder = 'services/' . $sellerId;
        $targetFolder = public_path('uploads/' . $relativeFolder);

        if (!file_exists($targetFolder)) {
            mkdir($targetFolder, 0755, true);
        }

        $fullPath = $targetFolder . '/' . $filename;
        file_put_contents($fullPath, $imageBinary);

        $imageRelativePath = $relativeFolder . '/' . $filename;

        // Create Thumbnail
        $thumbRelativePath = $relativeFolder . '/thumb_' . $filename;
        $thumbFullPath = $targetFolder . '/thumb_' . $filename;

        if (\App\Helpers\ImageHelper::createThumbnail($fullPath, $thumbFullPath, 600, 400, 80)) {
            $thumbnailPath = $thumbRelativePath;
        } else {
            $thumbnailPath = $imageRelativePath;
        }

        return [
            'gallery'   => [$imageRelativePath],
            'thumbnail' => $thumbnailPath,
        ];
    }
}
