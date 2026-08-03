<?php

namespace App\Services\AI;

use App\Models\AdminSettings;
use App\Models\CurrenciesExchange;
use App\Models\Currency;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ScopePricingEngine
{
    /**
     * Standard hourly rate benchmark in USD.
     * $25/hr standard development rate.
     */
    public const BASE_HOURLY_RATE_USD = 25.0;

    /**
     * Calculate valuation using Two-Level Realistic Pricing Formula:
     * Final Price = Project Overhead (Base Setup / Context Cost) + Sum(Component Marginal Costs)
     *
     * @param Project $project
     * @param array $components List of component keys, objects, or feature requirement strings.
     * @param array $options Additional options (context_type, hourly_rate_usd, currency_id).
     * @return array
     */
    public function calculateValuation(Project $project, array $components = [], array $options = []): array
    {
        $contextType = $options['context_type'] ?? $this->detectContextType($components, $project);

        // 1. Fetch USD and EGP currency models to determine the base USD -> EGP exchange rate
        $usdCurrency = Currency::where('currency', 'USD')->first();
        $egpCurrency = Currency::where('currency', 'EGP')->first();

        $usdToEgpRate = 50.0;
        if ($usdCurrency && $egpCurrency) {
            try {
                $rate = CurrenciesExchange::RateToday(1.0, $usdCurrency->id, $egpCurrency->id);
                if ($rate > 0) {
                    $usdToEgpRate = (float) $rate;
                } elseif (!empty($egpCurrency->rate) && $egpCurrency->rate > 0) {
                    $usdToEgpRate = (float) $egpCurrency->rate;
                }
            } catch (\Throwable $e) {
                $usdToEgpRate = (float) ($egpCurrency->rate ?? 50.0);
            }
        }

        // 2. Determine base hourly rate in USD
        $marketRateSetting = (float) (AdminSettings::GetValue('market_hourly_rate') ?: self::BASE_HOURLY_RATE_USD);
        if ($options['hourly_rate_usd'] ?? null) {
            $hourlyRate = (float) $options['hourly_rate_usd'];
        } elseif ($marketRateSetting > 100) {
            // Setting was entered in local EGP currency (e.g. 350 EGP/hr).
            // Convert EGP/hr to USD/hr using USD->EGP rate (e.g. 350 / 50 = $7.00 USD/hr)
            $hourlyRate = round($marketRateSetting / max(1.0, $usdToEgpRate), 2);
        } else {
            $hourlyRate = $marketRateSetting > 0 ? $marketRateSetting : self::BASE_HOURLY_RATE_USD;
        }

        // 3. Determine target currency and its exchange rate from USD
        $clientUser       = !empty($project->user_id) ? User::find($project->user_id) : null;
        $targetCurrencyId = $options['currency_id'] ?? $clientUser?->currency_id;
        $targetCurrency   = $targetCurrencyId ? Currency::find($targetCurrencyId) : null;

        $exchangeRate   = 1.0;
        $currencySymbol = '$';
        $currencyCode   = 'USD';

        if ($targetCurrency) {
            $currencySymbol = $targetCurrency->symbol ?? $targetCurrency->currency;
            $currencyCode   = $targetCurrency->currency;

            if ($usdCurrency && $targetCurrency->id !== $usdCurrency->id) {
                try {
                    $convertedRate = CurrenciesExchange::RateToday(1.0, $usdCurrency->id, $targetCurrency->id);
                    if ($convertedRate > 0) {
                        $exchangeRate = (float) $convertedRate;
                    } elseif (!empty($targetCurrency->rate) && $targetCurrency->rate > 0) {
                        $exchangeRate = (float) $targetCurrency->rate;
                    } else {
                        $exchangeRate = 50.0;
                    }
                } catch (\Throwable $e) {
                    $exchangeRate = (float) ($targetCurrency->rate ?? 50.0);
                }
            }
        }

        // Determine Project Overhead (Fixed Base Context Cost)
        $overheadHours = match ($contextType) {
            'NEW_PROJECT'              => 8, // Git, DB Architecture, Setup, Deploy, PM, QA
            'EXISTING_PROJECT_FEATURE' => 2, // Branching, Context Review, Integration Testing
            'BUG_FIX'                  => 1, // Diagnostics & Verification
            default                    => 4,
        };

        // Fetch official registered components catalog from DB (ContractPriceItem table)
        $registeredCatalog = ComponentBenchmarkRates::getComponents();

        // Normalize input description text
        $rawComponents = !empty($components) ? $components : ($project->ai_context['pending_features'] ?? []);
        if (empty($rawComponents) && !empty($project->project_name)) {
            $rawComponents = [$project->project_name];
        }

        $descriptionText = is_array($rawComponents) 
            ? implode(' ', array_map(fn($c) => is_string($c) ? $c : json_encode($c), $rawComponents)) 
            : (string) $rawComponents;

        if (!empty($project->description)) {
            $descriptionText .= ' ' . $project->description;
        }

        $cleanPromptText = trim($descriptionText);

        // Dynamic Platform & Tech Stack Detection
        $platformInfo = $this->detectPlatformInfo($cleanPromptText);
        $platformMultiplier = $platformInfo['multiplier'];

        // Try AI (Gemini) Analysis matching against registered DB catalog first
        $aiAnalysis = null;
        if (mb_strlen($cleanPromptText) >= 5) {
            $aiAnalysis = $this->analyzeScopeWithGemini($cleanPromptText, $registeredCatalog);
        }

        $aiSummary   = $aiAnalysis['ai_summary'] ?? null;
        $techStack   = $aiAnalysis['tech_stack'] ?? $platformInfo['tech_stack'];
        
        // Enforce tech_stack precision if a mobile platform (Android/Flutter/iOS) was requested
        if ($platformInfo['platform'] !== 'Web' || !str_contains($techStack, 'Laravel')) {
            $techStack = $platformInfo['tech_stack'];
        }

        $keyFeatures = $aiAnalysis['key_features'] ?? [];
        $selectedAiItems = $aiAnalysis['selected_components'] ?? null;

        $resolvedComponents = [];
        $sumComponentHours  = 0;

        if (!empty($selectedAiItems) && is_array($selectedAiItems)) {
            foreach ($selectedAiItems as $item) {
                $key = strtolower(trim($item['key'] ?? ''));
                $customNameAr = $item['name_ar'] ?? null;
                $customDesc = $item['description_ar'] ?? null;

                if (!empty($key) && isset($registeredCatalog[$key])) {
                    $catComp = $registeredCatalog[$key];
                    $baseH = ($contextType === 'NEW_PROJECT') ? ($catComp['marginal_hours'] ?? 4) : ($catComp['standalone_hours'] ?? 6);
                    if (!empty($item['hours'])) {
                        $baseH = (int) max(1, $item['hours']);
                    }
                    $h = (int) max(1, round($baseH * $platformMultiplier));
                    $compCostUsd = round($h * $hourlyRate, 2);
                    $sumComponentHours += $h;

                    $resolvedComponents[] = [
                        'key'             => $key,
                        'name_ar'         => $catComp['name_ar'],
                        'name_en'         => $catComp['name_en'],
                        'description_ar'  => $customDesc ?: 'بناء وتكامل الوحدة البرمجية المسجلة وفق معايير النظام.',
                        'complexity'      => strtolower($catComp['complexity'] ?? 'medium'),
                        'estimated_hours' => $h,
                        'cost_usd'        => $compCostUsd,
                    ];
                } elseif (!empty($customNameAr)) {
                    // Custom AI component not in catalog
                    $baseH = (int) max(1, $item['hours'] ?? 4);
                    $h = (int) max(1, round($baseH * $platformMultiplier));
                    $compCostUsd = round($h * $hourlyRate, 2);
                    $sumComponentHours += $h;

                    $resolvedComponents[] = [
                        'key'             => 'custom_' . md5($customNameAr),
                        'name_ar'         => $customNameAr,
                        'name_en'         => $item['name_en'] ?? 'Custom Feature',
                        'description_ar'  => $customDesc ?: 'تطوير وتنفيذ الميزة المخصصة.',
                        'complexity'      => strtolower($item['complexity'] ?? 'medium'),
                        'estimated_hours' => $h,
                        'cost_usd'        => $compCostUsd,
                    ];
                }
            }
        }

        // Fallback NLP Catalog Matcher if AI returned empty or failed
        if (empty($resolvedComponents)) {
            $matchedKeys = $this->detectRegisteredComponentsFromText($cleanPromptText, $registeredCatalog);

            $componentCount = count($matchedKeys);
            $scaleFactor = ($contextType === 'NEW_PROJECT' && $componentCount > 1)
                ? max(0.6, 1.0 - ($componentCount * 0.03))
                : 1.0;

            foreach ($matchedKeys as $key) {
                if (isset($registeredCatalog[$key])) {
                    $catComp = $registeredCatalog[$key];
                    $baseH = ($contextType === 'NEW_PROJECT')
                        ? ($catComp['marginal_hours'] ?? 4)
                        : ($catComp['standalone_hours'] ?? 6);

                    $h = (int) max(1, round($baseH * $scaleFactor * $platformMultiplier));
                    $compCostUsd = round($h * $hourlyRate, 2);
                    $sumComponentHours += $h;

                    $resolvedComponents[] = [
                        'key'             => $key,
                        'name_ar'         => $catComp['name_ar'],
                        'name_en'         => $catComp['name_en'],
                        'description_ar'  => 'بناء وتكامل مكون (' . $catComp['name_ar'] . ') المعتمد في نظام البرمجيات.',
                        'complexity'      => $catComp['complexity'] ?? 'medium',
                        'estimated_hours' => $h,
                        'cost_usd'        => $compCostUsd,
                    ];
                }
            }
        }

        // Add mobile integration features to keyFeatures if mobile platform requested
        if ($platformInfo['platform'] !== 'Web') {
            array_unshift($keyFeatures, 'تطوير وتكامل تطبيق الهواتف الجوالة (' . $platformInfo['label_ar'] . ')');
            if (!in_array('بناء وتأمين بروتوكولات REST API للتطبيق', $keyFeatures)) {
                $keyFeatures[] = 'بناء وتأمين بروتوكولات REST API للتطبيق';
            }
            $keyFeatures = array_unique($keyFeatures);
        }

        if (empty($aiSummary)) {
            $aiSummary = 'تم تحليل نطاق مشروع ' . $platformInfo['label_ar'] . ' بنجاح وتفكيك المتطلبات إلى ' . count($resolvedComponents) . ' مكونات برمجية مسجلة بالنظام مع التجهيز التشغيلي.';
        }

        if (empty($keyFeatures)) {
            $keyFeatures = array_column($resolvedComponents, 'name_ar');
        }

        // Total hours = Overhead + Component Hours
        $totalHours = $overheadHours + $sumComponentHours;

        // Build itemized micro-components list including Project Overhead as the first item
        $overheadCostUsd = round($overheadHours * $hourlyRate, 2);
        $overheadTitleAr = match ($contextType) {
            'NEW_PROJECT'              => 'التكلفة الثابتة والتجهيز التشغيلي للمشروع (Project Setup & Architecture)',
            'EXISTING_PROJECT_FEATURE' => 'مراجعة الكود والدمج المباشر (Context Review & Integration)',
            'BUG_FIX'                  => 'الفحص الفني والتأكيد الجوهري (Diagnostics & QA)',
            default                    => 'التكلفة التشغيلية للطلب (Operational Overhead)',
        };

        $itemizedComponents = array_merge([
            [
                'key'             => 'overhead',
                'name_ar'         => $overheadTitleAr,
                'name_en'         => 'Project Operational Overhead',
                'description_ar'  => 'إعداد بيئة العمل، تهيئة قاعدة البيانات، هيكلة Git، اختبارات الجودة الأوّلية وإدارة التنسيق.',
                'complexity'      => 'standard',
                'estimated_hours' => $overheadHours,
                'cost_usd'        => $overheadCostUsd,
            ]
        ], $resolvedComponents);

        $recommendedUsd = round($totalHours * $hourlyRate, 2);
        $minUsd         = round($recommendedUsd * 0.85, 2);
        $maxUsd         = round($recommendedUsd * 1.25, 2);
        $calculatedDays = (int) max(1, ceil($totalHours / 6)); // 6 productive hours per developer day

        $convertedAmount = round($recommendedUsd * $exchangeRate, 2);

        // Attach converted costs to itemized components
        foreach ($itemizedComponents as &$comp) {
            $comp['converted_cost']  = round($comp['cost_usd'] * $exchangeRate, 2);
            $comp['currency_symbol'] = $currencySymbol;
            $comp['currency_code']   = $currencyCode;
        }
        unset($comp);

        $depositUsd       = round($recommendedUsd * 0.50, 2);
        $depositConverted = round($convertedAmount * 0.50, 2);

        return [
            'ai_summary'           => $aiSummary,
            'tech_stack'           => $techStack,
            'key_features'         => array_values($keyFeatures),
            'context_type'         => $contextType,
            'platform'             => $platformInfo['platform'],
            'platform_label_ar'    => $platformInfo['label_ar'],
            'platform_multiplier' => $platformMultiplier,
            'type_key'             => 'component_based',
            'type_name_ar'         => 'تسعير ثنائي المستوى بالذكاء الاصطناعي (AI Two-Level Pricing Engine)',
            'type_name_en'         => 'AI Two-Level Realistic Valuation',
            'overhead_hours'       => $overheadHours,
            'overhead_cost_usd'    => $overheadCostUsd,
            'min_usd'              => $minUsd,
            'max_usd'              => $maxUsd,
            'recommended_usd'      => $recommendedUsd,
            'converted_amount'     => $convertedAmount,
            'deposit_usd'          => $depositUsd,
            'deposit_converted'    => $depositConverted,
            'currency_symbol'      => $currencySymbol,
            'currency_code'        => $currencyCode,
            'exchange_rate'        => $exchangeRate,
            'estimated_days'       => $calculatedDays,
            'total_hours'          => $totalHours,
            'complexity'           => $totalHours > 80 ? 'High' : ($totalHours > 30 ? 'Medium' : 'Standard'),
            'feature_breakdown'    => array_map(fn($c) => ['name' => $c['name_ar'], 'estimated_usd' => $c['cost_usd']], $itemizedComponents),
            'micro_components'     => $itemizedComponents,
            'granular_estimation'  => [
                'context_type'     => $contextType,
                'platform'         => $platformInfo['platform'],
                'total_usd'        => $recommendedUsd,
                'total_converted'  => $convertedAmount,
                'currency_symbol'  => $currencySymbol,
                'total_hours'      => $totalHours,
                'estimated_days'   => $calculatedDays,
                'components_count' => count($itemizedComponents),
                'micro_components' => $itemizedComponents,
            ],
        ];
    }

    /**
     * Detect platform requirements (Android, Flutter, iOS, Web) and calculate platform multiplier.
     */
    protected function detectPlatformInfo(string $prompt): array
    {
        $text = mb_strtolower($prompt);

        $hasAndroid = str_contains($text, 'android') || str_contains($text, 'اندرويد') || str_contains($text, 'أندرويد');
        $hasFlutter = str_contains($text, 'flutter') || str_contains($text, 'فلاتر');
        $hasIos     = str_contains($text, 'ios') || str_contains($text, 'ايفون') || str_contains($text, 'آيفون') || str_contains($text, 'swift');
        $hasMobile  = str_contains($text, 'mobile') || str_contains($text, 'موبايل') || str_contains($text, 'تطبيق');
        $hasWeb     = str_contains($text, 'web') || str_contains($text, 'موقع') || str_contains($text, 'react') || str_contains($text, 'laravel');

        if ($hasAndroid && ($hasWeb || str_contains($text, 'laravel') || str_contains($text, 'dashboard'))) {
            return [
                'platform'   => 'Laravel + Android Native',
                'tech_stack' => 'Laravel 12 (REST API & Admin Panel) + Android Native (Kotlin)',
                'multiplier' => 1.6,
                'label_ar'   => 'تطبيق أندرويد (Android Native Kotlin) + لوحة تحكم Laravel REST API',
            ];
        }

        if ($hasFlutter && ($hasWeb || str_contains($text, 'laravel') || str_contains($text, 'dashboard'))) {
            return [
                'platform'   => 'Laravel + Flutter',
                'tech_stack' => 'Laravel 12 (REST API & Admin Panel) + Flutter Cross-Platform (iOS & Android)',
                'multiplier' => 1.5,
                'label_ar'   => 'تطبيق جوال (Flutter iOS & Android) + لوحة تحكم Laravel REST API',
            ];
        }

        if ($hasAndroid) {
            return [
                'platform'   => 'Android Native',
                'tech_stack' => 'Android Native (Kotlin) + Laravel 12 (REST API)',
                'multiplier' => 1.5,
                'label_ar'   => 'تطبيق أندرويد أصلي (Android Native Kotlin)',
            ];
        }

        if ($hasFlutter) {
            return [
                'platform'   => 'Flutter',
                'tech_stack' => 'Flutter Cross-Platform (iOS & Android) + Laravel 12 (REST API)',
                'multiplier' => 1.4,
                'label_ar'   => 'تطبيق جوال متعدد المنصات (Flutter iOS & Android)',
            ];
        }

        if ($hasIos) {
            return [
                'platform'   => 'iOS Native',
                'tech_stack' => 'iOS Native (Swift) + Laravel 12 (REST API)',
                'multiplier' => 1.5,
                'label_ar'   => 'تطبيق آيفون أصلي (iOS Native Swift)',
            ];
        }

        if ($hasMobile) {
            return [
                'platform'   => 'Mobile App',
                'tech_stack' => 'Flutter Cross-Platform (iOS & Android) + Laravel 12 (REST API)',
                'multiplier' => 1.4,
                'label_ar'   => 'تطبيق جوال (Flutter Mobile App)',
            ];
        }

        return [
            'platform'   => 'Web',
            'tech_stack' => 'Laravel 12 / React (Inertia.js) / MySQL Database',
            'multiplier' => 1.0,
            'label_ar'   => 'منصة ويب متكاملة (Web Application)',
        ];
    }

    /**
     * NLP Multi-Keyword Matcher against all registered catalog components.
     */
    protected function detectRegisteredComponentsFromText(string $text, array $catalog): array
    {
        $normalizedText = mb_strtolower($text);
        $selectedKeys = [];

        foreach ($catalog as $key => $comp) {
            $keywords = is_array($comp['keywords']) ? $comp['keywords'] : [];
            
            // Check if any keyword matches the user prompt
            foreach ($keywords as $kw) {
                if (!empty($kw) && str_contains($normalizedText, mb_strtolower($kw))) {
                    $selectedKeys[] = $key;
                    break;
                }
            }
        }

        // Always ensure essential default components for web apps if prompt implies web project
        if (str_contains($normalizedText, 'موقع') || str_contains($normalizedText, 'تطبيق') || str_contains($normalizedText, 'app') || str_contains($normalizedText, 'system')) {
            if (!in_array('authentication', $selectedKeys)) {
                $selectedKeys[] = 'authentication';
            }
            if (!in_array('dashboard', $selectedKeys) && (str_contains($normalizedText, 'أدمن') || str_contains($normalizedText, 'admin') || str_contains($normalizedText, 'لوحة'))) {
                $selectedKeys[] = 'dashboard';
            }
            if (!in_array('users', $selectedKeys) && (str_contains($normalizedText, 'موظفين') || str_contains($normalizedText, 'عملاء') || str_contains($normalizedText, 'مستخدمين'))) {
                $selectedKeys[] = 'users';
            }
        }

        // If still empty, add default generic component
        if (empty($selectedKeys)) {
            $selectedKeys[] = 'tasks_todo';
        }

        return array_unique($selectedKeys);
    }

    /**
     * Call Gemini AI API to select matching registered database components from catalog.
     */
    protected function analyzeScopeWithGemini(string $description, array $catalog): ?array
    {
        $apiKeysString = AdminSettings::GetValue('gemini_api_keys') ?: AdminSettings::GetValue('gemini_api_key') ?: config('services.gemini.key');

        if (empty($apiKeysString)) {
            return null;
        }

        $keys = array_filter(array_map('trim', explode(',', $apiKeysString)));
        if (empty($keys)) {
            return null;
        }

        $apiKey = $keys[0];
        $model = AdminSettings::GetValue('gemini_model', 'gemini-2.0-flash');

        $catalogList = [];
        foreach ($catalog as $key => $c) {
            $catalogList[] = [
                'key'     => $key,
                'name_ar' => $c['name_ar'],
                'name_en' => $c['name_en'],
            ];
        }

        $prompt = "You are an expert enterprise software engineering estimator and technical consultant.\n"
            . "User Project Requirement Description:\n\"{$description}\"\n\n"
            . "OFFICIAL REGISTERED SYSTEM COMPONENTS CATALOG (JSON):\n" . json_encode($catalogList, JSON_UNESCAPED_UNICODE) . "\n\n"
            . "Instructions:\n"
            . "1. Analyze the project description carefully. Detect target platforms (e.g. Web, Flutter, Android Native, iOS Native, or Dual Web+Mobile).\n"
            . "2. Explicitly specify the 'tech_stack' field based on the user's prompt (e.g. if prompt mentions Android or Flutter, write 'Laravel 12 (REST API Backend) + Android Native (Kotlin)' or 'Laravel 12 (REST API) + Flutter Cross-Platform'). Do NOT default to Web if Android/Flutter/Mobile is requested!\n"
            . "3. Select ALL relevant component keys from the catalog above required to build this system.\n"
            . "4. For each selected component, provide a concise description in Arabic ('description_ar') explaining what will be developed for this specific project.\n"
            . "5. Provide an executive summary in Arabic ('ai_summary') and key deliverables list ('key_features').\n\n"
            . "Respond strictly with a JSON object matching this structure (no markdown wrappers):\n"
            . "{\n"
            . "  \"ai_summary\": \"ملخص تنفيذي دقيق للمشروع باللغة العربية...\",\n"
            . "  \"tech_stack\": \"Laravel 12 (REST API) + Android Native (Kotlin) / MySQL Database\",\n"
            . "  \"key_features\": [\"ميزة 1\", \"ميزة 2\", \"ميزة 3\"],\n"
            . "  \"selected_components\": [\n"
            . "    {\n"
            . "      \"key\": \"exact_catalog_key\",\n"
            . "      \"description_ar\": \"شرح مختصر باللغة العربية لما سيتم إنجازه لهذا المكون...\"\n"
            . "    }\n"
            . "  ]\n"
            . "}";

        try {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

            $response = Http::timeout(25)
                ->withHeaders(['Content-Type' => 'application/json'])
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
                    ],
                ]);

            if ($response->successful()) {
                $body = $response->json();
                $rawText = trim($body['candidates'][0]['content']['parts'][0]['text'] ?? '');

                // Clean markdown ```json wrappers if present
                $cleanJson = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $rawText);
                $decoded = json_decode(trim($cleanJson), true);

                if (is_array($decoded) && isset($decoded['selected_components']) && is_array($decoded['selected_components'])) {
                    return $decoded;
                }
            } else {
                Log::warning('Gemini AI API returned non-200 in ScopePricingEngine: ' . $response->body());
            }
        } catch (\Throwable $e) {
            Log::warning('Gemini AI scope estimation exception in ScopePricingEngine: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Auto-detect execution context type (NEW_PROJECT, EXISTING_PROJECT_FEATURE, BUG_FIX).
     */
    protected function detectContextType(array $components, Project $project): string
    {
        $text = mb_strtolower(implode(' ', array_map(fn($c) => is_array($c) ? ($c['name'] ?? '') : (string) $c, $components)) . ' ' . ($project->project_name ?? ''));

        if (str_contains($text, 'fix') || str_contains($text, 'bug') || str_contains($text, 'خطأ') || str_contains($text, 'إصلاح') || str_contains($text, 'مشكلة')) {
            return 'BUG_FIX';
        }

        if (!empty($project->approved_scope) || count($components) <= 2) {
            return 'EXISTING_PROJECT_FEATURE';
        }

        return 'NEW_PROJECT';
    }
}
