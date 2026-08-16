<?php

namespace App\Services\AI;

use App\Models\AdminSettings;
use App\Services\BaseService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProjectEstimatorAiService extends BaseService
{
    /**
     * Analyze project requirements and return structured estimator configuration.
     *
     * @param string $description
     * @return array|null
     */
    public function analyze(string $description): ?array
    {
        $prompt = $this->buildPrompt($description);

        $result = $this->callLLM($prompt);

        if (!$result || !is_array($result)) {
            return null;
        }

        return $this->sanitizeAndNormalizeResult($result);
    }

    /**
     * Build AI prompt detailing the available options, platforms, and output schema.
     */
    private function buildPrompt(string $description): string
    {
        return <<<PROMPT
You are a senior software architect and project estimation specialist at Musoftwares.
A client or administrator has provided the following project description or requirements:

--- PROJECT DESCRIPTION ---
{$description}
---------------------------

Analyze the requirements thoroughly and determine:
1. Target Platforms needed: Pick from ["web", "mobile", "desktop"] (can select multiple or single).
2. Number of Screens/Pages per selected platform (reasonable count between 1 and 60 based on complexity).
3. Recommended Modules/Add-ons: Pick ONLY from the available valid IDs listed below.

VALID MODULE IDs:
Web Modules:
- "web_admin_panel" (Web Admin Dashboard portal)
- "web_ai_chatbot" (AI Smart Assistant OpenAI/GPT)
- "web_whatsapp_bot" (WhatsApp Automation & OTP Alerts)
- "web_gateways" (Online Payment Gateways - counter 1 to 5)
- "web_pdf_invoicing" (Automated PDF Invoices & Receipts)
- "web_multi_currency" (Multi-Currency & GeoIP Detection)
- "web_marketing_pixels" (Marketing Pixels & GA4 Analytics)
- "web_seo_speed" (Technical SEO & Search Indexing)
- "web_auth_roles" (Multi-tier Roles & Permissions)
- "web_multilingual" (Multilingual Interface AR / EN)
- "web_security_firewall" (Security Firewall & DDoS Shield)
- "web_cloud_db" (Cloud Database & SSL Deployment)
- "web_external_api" (External REST APIs & Webhooks)
- "web_bug_fix" (Web Bug Fixing & Troubleshooting)
- "web_performance_tune" (Speed Tuning & 90+ PageSpeed)

Mobile Modules:
- "mobile_admin_panel" (Mobile Web Admin & REST API)
- "mobile_ai_assistant" (In-App AI Conversational Bot)
- "mobile_push_notifications" (Push Notifications FCM/OneSignal)
- "mobile_biometric_auth" (Biometric Login FaceID/Fingerprint)
- "mobile_app_stores" (App Store & Google Play Publishing)
- "mobile_gateways" (In-App Payment Gateways - counter 1 to 5)
- "mobile_social_auth" (Social & Apple/Google Login)
- "mobile_gps_maps" (GPS Maps & Geolocation Tracking)
- "mobile_live_chat" (In-App Live Support Chat)
- "mobile_deep_linking" (Deep Linking & Dynamic Share Links)
- "mobile_multilingual" (Multilingual App UI AR / EN)
- "mobile_external_api" (Mobile External REST APIs)
- "mobile_bug_fix" (Mobile Bug Fixing & Crash Resolution)
- "mobile_performance_tune" (App Speed & 60FPS Optimization)

Desktop Modules:
- "desktop_web_sync" (Cloud Master Sync & Web Portal)
- "desktop_e_invoicing_tax" (Electronic Invoicing ZATCA / ETA Tax)
- "desktop_whatsapp_receipts" (WhatsApp Invoice Direct Dispatch)
- "desktop_pos_printing" (Thermal POS & Barcode Label Printing)
- "desktop_offline_db" (100% Offline Database Engine)
- "desktop_auto_cloud_backup" (Encrypted Cloud Auto-Backup)
- "desktop_serial_license" (Hardware Serial Key & Licensing)
- "desktop_user_roles" (Cashier Shifts & Anti-Fraud Audit Log)
- "desktop_reporting_export" (Excel / PDF Export & Financial BI)
- "desktop_device_scanner" (Weight Scale & Scanner Hardware Sync)
- "desktop_bug_fix" (Desktop Bug Fixing & Data Repair)

RESPONSE FORMAT:
Return strictly a JSON object with this exact structure:
{
  "platforms": ["web", "mobile"],
  "platformScreens": {
    "web": 8,
    "mobile": 12,
    "desktop": 5
  },
  "selectedOptions": {
    "web_admin_panel": 1,
    "web_gateways": 1,
    "web_ai_chatbot": 1,
    "web_multilingual": 1,
    "mobile_push_notifications": 1
  },
  "summary_ar": "ملخص واضح ومختصر للمشروع باللغة العربية",
  "summary_en": "Clear concise project scope summary in English",
  "recommended_reasons": [
    "سبب اختيار الصفحات والوحدات الرئيسية 1",
    "سبب اختيار الصفحات والوحدات الرئيسية 2"
  ]
}
PROMPT;
    }

    /**
     * Execute LLM call with fallback (Gemini -> OpenAI).
     */
    private function callLLM(string $prompt): ?array
    {
        // 1. Try Gemini first
        $geminiKeys = AdminSettings::GetValue('gemini_api_keys') 
            ?: AdminSettings::GetValue('gemini_api_key') 
            ?: config('services.gemini.key');

        if ($geminiKeys) {
            $keys = array_filter(array_map('trim', explode(',', $geminiKeys)));
            $geminiKey = $keys[0] ?? null;
            if ($geminiKey) {
                $res = $this->callGemini($prompt, $geminiKey);
                if ($res) {
                    return $res;
                }
            }
        }

        // 2. Try OpenAI fallback
        $openAiKey = AdminSettings::GetValue('openai_api_key') ?: config('services.openai.key');
        if ($openAiKey) {
            $res = $this->callOpenAI($prompt, $openAiKey);
            if ($res) {
                return $res;
            }
        }

        Log::error('ProjectEstimatorAiService: No working AI credentials or all calls failed.');
        return null;
    }

    /**
     * Call Gemini API
     */
    private function callGemini(string $prompt, string $apiKey): ?array
    {
        $model = AdminSettings::GetValue('gemini_model', 'gemini-2.0-flash');

        try {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

            $response = Http::withoutVerifying()
                ->timeout(45)
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
                    $content = preg_replace('/^```json\s*|\s*```$/m', '', trim($content));
                    $decoded = json_decode(trim($content), true);
                    if (is_array($decoded)) {
                        return $decoded;
                    }
                }
            }

            Log::warning('Gemini API error in ProjectEstimatorAiService', ['response' => $response->body()]);
            return null;
        } catch (\Throwable $e) {
            Log::error('Exception in ProjectEstimatorAiService callGemini: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Call OpenAI API
     */
    private function callOpenAI(string $prompt, string $apiKey): ?array
    {
        $model = AdminSettings::GetValue('openai_model') ?: 'gpt-4o-mini';

        try {
            $response = Http::withoutVerifying()
                ->timeout(45)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . trim($apiKey),
                    'Content-Type'  => 'application/json',
                ])
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $model,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        [
                            'role' => 'system', 
                            'content' => 'You are an expert software project estimator that outputs strictly JSON.'
                        ],
                        [
                            'role' => 'user', 
                            'content' => $prompt
                        ],
                    ],
                    'temperature' => 0.2,
                ]);

            if ($response->successful()) {
                $content = $response->json('choices.0.message.content');
                if ($content !== null) {
                    $decoded = json_decode(trim($content), true);
                    if (is_array($decoded)) {
                        return $decoded;
                    }
                }
            }

            Log::warning('OpenAI API error in ProjectEstimatorAiService', ['response' => $response->body()]);
            return null;
        } catch (\Throwable $e) {
            Log::error('Exception in ProjectEstimatorAiService callOpenAI: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Sanitize and enforce bounds on returned estimation data.
     */
    private function sanitizeAndNormalizeResult(array $data): array
    {
        $validPlatforms = ['web', 'mobile', 'desktop'];
        $rawPlatforms = $data['platforms'] ?? ['web'];
        if (!is_array($rawPlatforms)) {
            $rawPlatforms = [$rawPlatforms];
        }

        $platforms = array_values(array_intersect($rawPlatforms, $validPlatforms));
        if (empty($platforms)) {
            $platforms = ['web'];
        }

        $rawScreens = $data['platformScreens'] ?? [];
        $screens = [
            'web' => max(1, min(60, (int)($rawScreens['web'] ?? 5))),
            'mobile' => max(1, min(60, (int)($rawScreens['mobile'] ?? 5))),
            'desktop' => max(1, min(60, (int)($rawScreens['desktop'] ?? 5))),
        ];

        $rawOptions = $data['selectedOptions'] ?? [];
        $selectedOptions = [];
        if (is_array($rawOptions)) {
            foreach ($rawOptions as $key => $val) {
                if (is_string($key) && $val) {
                    $selectedOptions[$key] = is_numeric($val) ? max(1, min(5, (int)$val)) : 1;
                }
            }
        }

        return [
            'platforms' => $platforms,
            'platformScreens' => $screens,
            'selectedOptions' => $selectedOptions,
            'summary_ar' => (string)($data['summary_ar'] ?? ''),
            'summary_en' => (string)($data['summary_en'] ?? ''),
            'recommended_reasons' => is_array($data['recommended_reasons'] ?? null) 
                ? array_values(array_filter($data['recommended_reasons'])) 
                : [],
        ];
    }
}
