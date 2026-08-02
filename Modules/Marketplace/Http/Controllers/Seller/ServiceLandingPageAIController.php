<?php

namespace Modules\Marketplace\Http\Controllers\Seller;

use Modules\Marketplace\Models\Service;
use Modules\Marketplace\Models\ServiceLandingPage;
use Modules\Marketplace\Models\ServiceLandingQuestion;
use Modules\Marketplace\Models\ServiceLandingFaq;
use Modules\Marketplace\Models\ServiceLandingPricingTable;
use Modules\Marketplace\Models\ServiceLandingFormSubmission;
use Modules\Marketplace\Models\ServiceLandingPageCtaVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Inertia\Inertia;
use App\Http\Controllers\Controller;

class ServiceLandingPageAIController extends Controller
{


    public function generateQuestions(Request $request, Service $service)
    {
        $this->authorize('update', $service);

        $landingPage = $service->landingPage;
        if (!$landingPage) {
            return response()->json([
                'success' => false,
                'message' => 'Landing page not found. Please create one first.'
            ], 404);
        }

        $modelInfo = $this->getCurrentModelAndKey();
        if (isset($modelInfo['error'])) {
            return response()->json([
                'success' => false,
                'message' => $modelInfo['error']
            ], 400);
        }

        try {
            $serviceTitle = $request->input('service_title', $service->title);
            $heroTitle = $request->input('hero_title', $landingPage->hero_title);
            $heroDescription = $request->input('hero_description', $landingPage->hero_description);
            $description = $request->input('description', $landingPage->description);

            // Build prompt for generating form questions
            $prompt = "You are an expert in creating effective landing page forms. Based on the following service information, generate 5-8 relevant form questions that would help collect useful information from potential customers.\n\n";
            $prompt .= "Service Title: {$serviceTitle}\n";
            $prompt .= "Hero Title: {$heroTitle}\n";
            if ($heroDescription) {
                $prompt .= "Hero Description: {$heroDescription}\n";
            }
            if ($description) {
                $prompt .= "Service Description: " . strip_tags($description) . "\n";
            }
            $prompt .= "\nGenerate form questions that are:\n";
            $prompt .= "1. Relevant to the service\n";
            $prompt .= "2. Helpful for understanding customer needs\n";
            $prompt .= "3. Appropriate field types (text, email, phone, textarea, select, etc.)\n";
            $prompt .= "4. Include helpful placeholders and help text when appropriate\n\n";
            $prompt .= "Return ONLY a valid JSON array in this exact format:\n";
            $prompt .= "[\n";
            $prompt .= "  {\n";
            $prompt .= "    \"question_text\": \"What is your budget range?\",\n";
            $prompt .= "    \"field_type\": \"select\",\n";
            $prompt .= "    \"is_required\": true,\n";
            $prompt .= "    \"placeholder\": \"Select your budget\",\n";
            $prompt .= "    \"help_text\": \"This helps us provide the best solution for your needs\",\n";
            $currencySymbol = optional($service->currency)->symbol ?: '$';
            $prompt .= "    \"field_options\": [\"Under {$currencySymbol}500\", \"{$currencySymbol}500-{$currencySymbol}1000\", \"{$currencySymbol}1000-{$currencySymbol}5000\", \"{$currencySymbol}5000+\"]\n";
            $prompt .= "  }\n";
            $prompt .= "]\n\n";
            $prompt .= "Field types can be: text, textarea, email, phone, number, date, select, radio, checkbox";

            // Call appropriate API based on user preference
            if ($modelInfo['provider'] === 'openai') {
                $content = $this->callOpenAIAPI($modelInfo['api_key'], $modelInfo['model'], $prompt);
                if (isset($content['error'])) {
                    return response()->json([
                        'success' => false,
                        'message' => $content['error']
                    ], 500);
                }
                $content = $content['content'] ?? '';
            } else {
                $response = $this->callGeminiAPI($modelInfo['api_key'], $modelInfo['model'], $prompt);
                if ($response->failed()) {
                    $statusCode = $response->status();
                    $errorMessage = $response->json()['error']['message'] ?? 'Unknown API error';
                    return response()->json([
                        'success' => false,
                        'message' => "AI API Error ($statusCode): $errorMessage"
                    ], 500);
                }
                $aiResponse = $response->json();
                $content = $aiResponse['candidates'][0]['content']['parts'][0]['text'] ?? 'No AI response';
            }

            // Clean and parse JSON
            $cleanContent = preg_replace('/```json\s*/', '', $content);
            $cleanContent = preg_replace('/```\s*/', '', $cleanContent);
            $cleanContent = trim($cleanContent);

            $questions = json_decode($cleanContent, true);

            if (json_last_error() !== JSON_ERROR_NONE || !is_array($questions)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to parse AI response. Please try again.'
                ], 500);
            }

            // Validate and format questions
            $formattedQuestions = [];
            foreach ($questions as $question) {
                if (isset($question['question_text']) && !empty($question['question_text'])) {
                    $formattedQuestions[] = [
                        'question_text' => $question['question_text'],
                        'field_type' => $question['field_type'] ?? 'text',
                        'is_required' => $question['is_required'] ?? false,
                        'placeholder' => $question['placeholder'] ?? null,
                        'help_text' => $question['help_text'] ?? null,
                        'field_options' => $question['field_options'] ?? null,
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'questions' => $formattedQuestions,
                'message' => 'Questions generated successfully using ' . strtoupper($modelInfo['model']) . '!'
            ]);

        } catch (\Exception $e) {
            Log::error('AI Question Generation Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }



    public function generateFAQs(Request $request, Service $service)
    {
        $this->authorize('update', $service);

        $landingPage = $service->landingPage;
        if (!$landingPage) {
            return response()->json([
                'success' => false,
                'message' => 'Landing page not found. Please create one first.'
            ], 404);
        }

        $modelInfo = $this->getCurrentModelAndKey();
        if (isset($modelInfo['error'])) {
            return response()->json([
                'success' => false,
                'message' => $modelInfo['error']
            ], 400);
        }

        try {
            $serviceTitle = $request->input('service_title', $service->title);
            $heroTitle = $request->input('hero_title', $landingPage->hero_title);
            $heroDescription = $request->input('hero_description', $landingPage->hero_description);
            $description = $request->input('description', $landingPage->description);

            // Build prompt for generating FAQs
            $prompt = "You are an expert in creating helpful FAQ sections for landing pages. Based on the following service information, generate 6-10 relevant and helpful FAQs that potential customers would likely ask.\n\n";
            $prompt .= "Service Title: {$serviceTitle}\n";
            $prompt .= "Hero Title: {$heroTitle}\n";
            if ($heroDescription) {
                $prompt .= "Hero Description: {$heroDescription}\n";
            }
            if ($description) {
                $prompt .= "Service Description: " . strip_tags($description) . "\n";
            }
            $prompt .= "\nGenerate FAQs that:\n";
            $prompt .= "1. Address common customer concerns and questions\n";
            $prompt .= "2. Are relevant to the specific service\n";
            $prompt .= "3. Have clear, helpful, and concise answers\n";
            $prompt .= "4. Cover topics like pricing, process, timeline, guarantees, etc.\n\n";
            $prompt .= "Return ONLY a valid JSON array in this exact format:\n";
            $prompt .= "[\n";
            $prompt .= "  {\n";
            $prompt .= "    \"question\": \"How long does it take to complete a project?\",\n";
            $prompt .= "    \"answer\": \"The timeline depends on the project scope and complexity. Typically, projects range from 2-8 weeks. We'll provide a detailed timeline after reviewing your specific requirements.\"\n";
            $prompt .= "  }\n";
            $prompt .= "]\n\n";
            $prompt .= "Make sure answers are informative, professional, and helpful.";

            // Call appropriate API based on user preference
            if ($modelInfo['provider'] === 'openai') {
                $content = $this->callOpenAIAPI($modelInfo['api_key'], $modelInfo['model'], $prompt);
                if (isset($content['error'])) {
                    return response()->json([
                        'success' => false,
                        'message' => $content['error']
                    ], 500);
                }
                $content = $content['content'] ?? '';
            } else {
                $response = $this->callGeminiAPI($modelInfo['api_key'], $modelInfo['model'], $prompt);
                if ($response->failed()) {
                    $statusCode = $response->status();
                    $errorMessage = $response->json()['error']['message'] ?? 'Unknown API error';
                    return response()->json([
                        'success' => false,
                        'message' => "AI API Error ($statusCode): $errorMessage"
                    ], 500);
                }
                $aiResponse = $response->json();
                $content = $aiResponse['candidates'][0]['content']['parts'][0]['text'] ?? 'No AI response';
            }

            // Clean and parse JSON
            $cleanContent = preg_replace('/```json\s*/', '', $content);
            $cleanContent = preg_replace('/```\s*/', '', $cleanContent);
            $cleanContent = trim($cleanContent);

            $faqs = json_decode($cleanContent, true);

            if (json_last_error() !== JSON_ERROR_NONE || !is_array($faqs)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to parse AI response. Please try again.'
                ], 500);
            }

            // Validate and format FAQs
            $formattedFAQs = [];
            foreach ($faqs as $faq) {
                if (isset($faq['question']) && !empty($faq['question']) &&
                    isset($faq['answer']) && !empty($faq['answer'])) {
                    $formattedFAQs[] = [
                        'question' => $faq['question'],
                        'answer' => $faq['answer'],
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'faqs' => $formattedFAQs,
                'message' => 'FAQs generated successfully using ' . strtoupper($modelInfo['model']) . '!'
            ]);

        } catch (\Exception $e) {
            Log::error('AI FAQ Generation Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }



    public function generatePricingTables(Request $request, Service $service)
    {
        $this->authorize('update', $service);

        $landingPage = $service->landingPage;
        if (!$landingPage) {
            return response()->json([
                'success' => false,
                'message' => 'Landing page not found. Please create one first.'
            ], 404);
        }

        $modelInfo = $this->getCurrentModelAndKey();
        if (isset($modelInfo['error'])) {
            return response()->json([
                'success' => false,
                'message' => $modelInfo['error']
            ], 400);
        }

        try {
            $serviceTitle = $request->input('service_title', $service->title);
            $servicePrice = $service->price ?? 0;
            // Get currency code from Currency model
            $serviceCurrency = null;
            if ($service->currency) {
                $currencyModel = \App\Models\Currency::find($service->currency);
                $serviceCurrency = $currencyModel ? $currencyModel->currency : 'USD';
            }
            $heroTitle = $request->input('hero_title', $landingPage->hero_title);
            $heroDescription = $request->input('hero_description', $landingPage->hero_description);
            $description = $request->input('description', $landingPage->description);

            // Build prompt for generating pricing tables
            $prompt = "You are an expert in creating effective pricing tables for landing pages. Based on the following service information, generate 2-4 pricing plans that are competitive, clear, and conversion-focused.\n\n";
            $prompt .= "Service Information:\n";
            $prompt .= "Service Title: {$serviceTitle}\n";
            if ($heroTitle) {
                $prompt .= "Hero Title: {$heroTitle}\n";
            }
            if ($heroDescription) {
                $prompt .= "Hero Description: {$heroDescription}\n";
            }
            if ($description) {
                $prompt .= "Service Description: " . strip_tags($description) . "\n";
            }
            if ($servicePrice > 0) {
                $prompt .= "Base Price: {$servicePrice}\n";
            }
            if ($serviceCurrency) {
                $prompt .= "Currency: {$serviceCurrency}\n";
            }
            $prompt .= "\nGenerate pricing plans that:\n";
            $prompt .= "1. Are relevant to the service type\n";
            $prompt .= "2. Include clear value propositions\n";
            $prompt .= "3. Have appropriate pricing tiers (starter, professional, enterprise, etc.)\n";
            $prompt .= "4. Include 4-8 features per plan\n";
            $prompt .= "5. One plan should be marked as popular/recommended\n";
            $prompt .= "6. Use appropriate pricing periods (per month, one-time, per year, etc.)\n\n";
            $prompt .= "Return ONLY a valid JSON array in this exact format:\n";
            $prompt .= "[\n";
            $prompt .= "  {\n";
            $prompt .= "    \"plan_name\": \"Starter Plan\",\n";
            $prompt .= "    \"description\": \"Perfect for individuals getting started\",\n";
            $prompt .= "    \"price\": 29.99,\n";
            $prompt .= "    \"currency_id\": 1,\n";
            $prompt .= "    \"period\": \"per month\",\n";
            $prompt .= "    \"features\": [\"Feature 1\", \"Feature 2\", \"Feature 3\"],\n";
            $prompt .= "    \"is_popular\": false,\n";
            $prompt .= "    \"cta_text\": \"Get Started\"\n";
            $prompt .= "  },\n";
            $prompt .= "  {\n";
            $prompt .= "    \"plan_name\": \"Professional Plan\",\n";
            $prompt .= "    \"description\": \"Best for growing businesses\",\n";
            $prompt .= "    \"price\": 99.99,\n";
            $prompt .= "    \"currency_id\": 1,\n";
            $prompt .= "    \"period\": \"per month\",\n";
            $prompt .= "    \"features\": [\"All Starter features\", \"Advanced Feature 1\", \"Advanced Feature 2\"],\n";
            $prompt .= "    \"is_popular\": true,\n";
            $prompt .= "    \"cta_text\": \"Get Started\"\n";
            $prompt .= "  }\n";
            $prompt .= "]\n\n";
            $prompt .= "Make sure pricing is realistic, competitive, and appropriate for the service type. Include clear, valuable features that differentiate each tier.";

            // Call appropriate API based on user preference
            if ($modelInfo['provider'] === 'openai') {
                $content = $this->callOpenAIAPI($modelInfo['api_key'], $modelInfo['model'], $prompt);
                if (isset($content['error'])) {
                    return response()->json([
                        'success' => false,
                        'message' => $content['error']
                    ], 500);
                }
                $content = $content['content'] ?? '';
            } else {
                $response = $this->callGeminiAPI($modelInfo['api_key'], $modelInfo['model'], $prompt);
                if ($response->failed()) {
                    $statusCode = $response->status();
                    $errorMessage = $response->json()['error']['message'] ?? 'Unknown API error';
                    return response()->json([
                        'success' => false,
                        'message' => "AI API Error ($statusCode): $errorMessage"
                    ], 500);
                }
                $aiResponse = $response->json();
                $content = $aiResponse['candidates'][0]['content']['parts'][0]['text'] ?? 'No AI response';
            }

            // Clean and parse JSON
            $cleanContent = preg_replace('/```json\s*/', '', $content);
            $cleanContent = preg_replace('/```\s*/', '', $cleanContent);
            $cleanContent = trim($cleanContent);

            $pricingTables = json_decode($cleanContent, true);

            if (json_last_error() !== JSON_ERROR_NONE || !is_array($pricingTables)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to parse AI response. Please try again.'
                ], 500);
            }

            // Validate and format pricing tables
            $formattedPricing = [];
            foreach ($pricingTables as $pricing) {
                if (isset($pricing['plan_name']) && !empty($pricing['plan_name'])) {
                    $formattedPricing[] = [
                        'plan_name' => $pricing['plan_name'],
                        'description' => $pricing['description'] ?? null,
                        'price' => $pricing['price'] ?? 0,
                        'currency_id' => $pricing['currency_id'] ?? 1,
                        'period' => $pricing['period'] ?? null,
                        'features' => $pricing['features'] ?? [],
                        'is_popular' => $pricing['is_popular'] ?? false,
                        'cta_text' => $pricing['cta_text'] ?? 'Get Started',
                        'cta_link' => $pricing['cta_link'] ?? null,
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'pricing_tables' => $formattedPricing,
                'message' => 'Pricing tables generated successfully using ' . strtoupper($modelInfo['model']) . '!'
            ]);

        } catch (\Exception $e) {
            Log::error('AI Pricing Table Generation Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }



    public function generateLandingPageContent(Request $request, Service $service)
    {
        $this->authorize('update', $service);

        $modelInfo = $this->getCurrentModelAndKey();
        if (isset($modelInfo['error'])) {
            return response()->json([
                'success' => false,
                'message' => $modelInfo['error']
            ], 400);
        }

        try {
            $serviceTitle = $service->title;
            $serviceDescription = strip_tags($service->description ?? '');
            $serviceImage = $service->image ? asset($service->image) : '';
            $servicePrice = $service->price ?? 0;
            $serviceCategory = $service->category ?? '';

            // Check if this is a specific rewrite request
            if ($request->input('prompt_type') === 'rewrite') {
                $currentText = $request->input('current_text');
                $fieldName = $request->input('field_name');
                
                $prompt = "You are a professional copywriter. Rewrite the following text to be more persuasive, clear, and conversion-focused. Maintain the original meaning but improve the style/tone.";
                if ($fieldName === 'hero_title') {
                    $prompt .= " This is a Hero Title (max 80 chars). Make it punchy and benefit-driven.";
                } elseif ($fieldName === 'hero_description') {
                    $prompt .= " This is a Hero Description (150-200 chars). Focus on value proposition.";
                }
                
                $prompt .= "\n\nOriginal Text:\n\"{$currentText}\"\n\n";
                $prompt .= "Return ONLY the rewritten text (no quotes, no preamble).";

                if ($modelInfo['provider'] === 'openai') {
                    $contentResult = $this->callOpenAIAPI($modelInfo['api_key'], $modelInfo['model'], $prompt);
                    $rewrittenText = $contentResult['content'] ?? '';
                    $error = $contentResult['error'] ?? null;
                } else {
                    $response = $this->callGeminiAPI($modelInfo['api_key'], $modelInfo['model'], $prompt);
                    if ($response->failed()) {
                        $error = $response->json()['error']['message'] ?? 'API Error';
                    } else {
                        $rewrittenText = $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? '';
                    }
                }

                if (isset($error) || empty($rewrittenText)) {
                    return response()->json([
                        'success' => false,
                        'message' => $error ?? 'Failed to generate text'
                    ], 500);
                }

                return response()->json([
                    'success' => true,
                    'content' => trim($rewrittenText)
                ]);
            }

            // Build prompt for generating complete landing page content
            $prompt = "You are an expert in creating high-converting landing pages. Based on the following service information, generate optimized landing page content that is compelling, SEO-friendly, and conversion-focused.\n\n";
            $prompt .= "Service Information:\n";
            $prompt .= "Title: {$serviceTitle}\n";
            if ($serviceDescription) {
                $prompt .= "Description: {$serviceDescription}\n";
            }
            if ($servicePrice > 0) {
                $prompt .= "Price: {$servicePrice}\n";
            }
            if ($serviceCategory) {
                $prompt .= "Category: {$serviceCategory}\n";
            }
            $prompt .= "\nGenerate optimized landing page content with the following requirements:\n";
            $prompt .= "1. Slug: Create an SEO-friendly URL slug (lowercase, hyphens instead of spaces, no special characters, 3-8 words max). Make it memorable and descriptive of the service. Example: \"professional-web-design-services\" or \"affordable-seo-consulting\"\n";
            $prompt .= "2. Hero Title: Create a compelling, attention-grabbing headline (max 80 characters) that highlights the main value proposition\n";
            $prompt .= "3. Hero Description: Write a concise, persuasive description (150-200 characters) that explains the key benefits\n";
            $prompt .= "4. Description: Create a detailed, engaging description (HTML format) that explains the service, its benefits, features, and why customers should choose it. Make it professional and conversion-focused.\n";
            $prompt .= "5. Meta Title: SEO-optimized title (50-60 characters) for search engines\n";
            $prompt .= "6. Meta Description: SEO-optimized description (150-160 characters) for search engines\n";
            $prompt .= "7. Meta Keywords: Relevant keywords (comma-separated, 5-10 keywords)\n";
            $prompt .= "8. OG Title: Social media optimized title for Open Graph (max 100 characters)\n";
            $prompt .= "9. OG Description: Social media optimized description for Open Graph (max 300 characters)\n";
            $prompt .= "10. Twitter Title: Twitter-optimized title (max 70 characters)\n";
            $prompt .= "11. Twitter Description: Twitter-optimized description (max 200 characters)\n\n";
            $prompt .= "Return ONLY a valid JSON object in this exact format:\n";
            $prompt .= "{\n";
            $prompt .= "  \"slug\": \"seo-friendly-url-slug\",\n";
            $prompt .= "  \"hero_title\": \"Compelling headline here\",\n";
            $prompt .= "  \"hero_description\": \"Short compelling description\",\n";
            $prompt .= "  \"description\": \"<p>Detailed HTML description with formatting</p>\",\n";
            $prompt .= "  \"meta_title\": \"SEO optimized title\",\n";
            $prompt .= "  \"meta_description\": \"SEO optimized description\",\n";
            $prompt .= "  \"meta_keywords\": \"keyword1, keyword2, keyword3\",\n";
            $prompt .= "  \"og_title\": \"Social media title\",\n";
            $prompt .= "  \"og_description\": \"Social media description\",\n";
            $prompt .= "  \"twitter_title\": \"Twitter title\",\n";
            $prompt .= "  \"twitter_description\": \"Twitter description\"\n";
            $prompt .= "}\n\n";
            $prompt .= "Make sure all content is professional, engaging, and optimized for conversions and SEO. The slug must be URL-friendly (lowercase, hyphens, no spaces or special characters).";

            // Call appropriate API based on user preference
            if ($modelInfo['provider'] === 'openai') {
                $content = $this->callOpenAIAPI($modelInfo['api_key'], $modelInfo['model'], $prompt);
                if (isset($content['error'])) {
                    return response()->json([
                        'success' => false,
                        'message' => $content['error']
                    ], 500);
                }
                $content = $content['content'] ?? '';
            } else {
                $response = $this->callGeminiAPI($modelInfo['api_key'], $modelInfo['model'], $prompt);
                if ($response->failed()) {
                    $statusCode = $response->status();
                    $errorMessage = $response->json()['error']['message'] ?? 'Unknown API error';
                    return response()->json([
                        'success' => false,
                        'message' => "AI API Error ($statusCode): $errorMessage"
                    ], 500);
                }
                $aiResponse = $response->json();
                $content = $aiResponse['candidates'][0]['content']['parts'][0]['text'] ?? 'No AI response';
            }

            // Clean and parse JSON
            $cleanContent = preg_replace('/```json\s*/', '', $content);
            $cleanContent = preg_replace('/```\s*/', '', $cleanContent);
            $cleanContent = trim($cleanContent);

            $landingPageData = json_decode($cleanContent, true);

            if (json_last_error() !== JSON_ERROR_NONE || !is_array($landingPageData)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to parse AI response. Please try again.'
                ], 500);
            }

            // Validate and format the data
            // Clean and sanitize the AI-generated slug
            $aiSlug = $landingPageData['slug'] ?? '';
            if (!empty($aiSlug)) {
                // Ensure slug is URL-friendly: lowercase, replace spaces/special chars with hyphens
                $aiSlug = Str::slug($aiSlug);
            }
            // Fallback to service title slug if AI didn't generate one or it's invalid
            if (empty($aiSlug)) {
                $aiSlug = Str::slug($serviceTitle);
            }

            $formattedData = [
                'slug' => $aiSlug,
                'hero_title' => $landingPageData['hero_title'] ?? $serviceTitle,
                'hero_description' => $landingPageData['hero_description'] ?? '',
                'hero_cta_text' => 'Get Started',
                'description' => $landingPageData['description'] ?? $service->description ?? '',
                'meta_title' => $landingPageData['meta_title'] ?? '',
                'meta_description' => $landingPageData['meta_description'] ?? '',
                'meta_keywords' => $landingPageData['meta_keywords'] ?? '',
                'og_title' => $landingPageData['og_title'] ?? '',
                'og_description' => $landingPageData['og_description'] ?? '',
                'og_image' => $serviceImage,
                'twitter_title' => $landingPageData['twitter_title'] ?? '',
                'twitter_description' => $landingPageData['twitter_description'] ?? '',
                'twitter_image' => $serviceImage,
                'twitter_card_type' => 'summary_large_image',
                'robots' => 'index, follow',
            ];

            return response()->json([
                'success' => true,
                'data' => $formattedData,
                'message' => 'Landing page content generated successfully using ' . strtoupper($modelInfo['model']) . '!'
            ]);

        } catch (\Exception $e) {
            Log::error('AI Landing Page Content Generation Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }



    private function callGeminiAPI($apiKey, $model, $prompt)
    {
        $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models';
        $endpoint = "{$baseUrl}/{$model}:generateContent?key={$apiKey}";

        $contents = [
            [
                'parts' => [
                    ['text' => $prompt]
                ]
            ]
        ];

        return Http::timeout(300)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($endpoint, ['contents' => $contents]);
    }


    private function getCurrentModelAndKey(): array
    {
        $user = Auth::user();
        $defaultProvider = 'gemini';

        $apiKey = $user->gemini_api ?? null;
        if (empty($apiKey)) {
            return [
                'error' => 'Gemini API key is required. Please set it in your profile settings first.'
            ];
        }
        $model = $user->gemini_model ?? 'gemini-2.0-flash';
        return ['provider' => 'gemini', 'model' => $model, 'api_key' => $apiKey];
    }


    private function callOpenAIAPI(string $apiKey, string $model, string $prompt): array
    {
        $systemPrompt = "You are an expert in creating high-converting landing pages. Your task is to generate professional, detailed landing page content based on service information.";
        $userMessage = $prompt;

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userMessage]
        ];

        try {
            $response = Http::timeout(300)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $model,
                    'messages' => $messages,
                    'temperature' => 0.7,
                    'max_tokens' => 4000,
                ]);
        } catch (\Throwable $e) {
            return ['error' => 'Network Error: ' . $e->getMessage()];
        }

        if ($response->failed()) {
            $statusCode = $response->status();
            $json = $response->json();
            $errorMessage = $json['error']['message'] ?? 'Unknown API error';

            if ($statusCode === 401 || $statusCode === 403) {
                $errorMessage .= ' (Check API key / model access)';
            } elseif ($statusCode === 429) {
                $errorMessage .= ' (Rate limited: try again)';
            }

            return ['error' => "API Error ($statusCode): $errorMessage"];
        }

        $aiResponse = $response->json();
        $content = $aiResponse['choices'][0]['message']['content'] ?? 'No AI response';

        return ['content' => trim($content)];
    }

}
