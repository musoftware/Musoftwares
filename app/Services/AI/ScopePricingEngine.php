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
        $usdToEgpRate = 50.0;
        $usdCurrency = null;
        $egpCurrency = null;

        try {
            $usdCurrency = Currency::where('currency', 'USD')->first();
            $egpCurrency = Currency::where('currency', 'EGP')->first();

            if ($usdCurrency && $egpCurrency) {
                $rate = CurrenciesExchange::RateToday(1.0, $usdCurrency->id, $egpCurrency->id);
                if ($rate > 0) {
                    $usdToEgpRate = (float) $rate;
                } elseif (!empty($egpCurrency->rate) && $egpCurrency->rate > 0) {
                    $usdToEgpRate = (float) $egpCurrency->rate;
                }
            }
        } catch (\Throwable $e) {
            $usdToEgpRate = 50.0;
        }

        // 2. Determine base hourly rate in USD
        $marketRateSetting = self::BASE_HOURLY_RATE_USD;
        try {
            $settingVal = AdminSettings::GetValue('market_hourly_rate');
            if ($settingVal) {
                $marketRateSetting = (float) $settingVal;
            }
        } catch (\Throwable $e) {
            $marketRateSetting = self::BASE_HOURLY_RATE_USD;
        }

        if ($options['hourly_rate_usd'] ?? null) {
            $hourlyRate = (float) $options['hourly_rate_usd'];
        } elseif ($marketRateSetting > 100) {
            $hourlyRate = round($marketRateSetting / max(1.0, $usdToEgpRate), 2);
        } else {
            $hourlyRate = $marketRateSetting > 0 ? $marketRateSetting : self::BASE_HOURLY_RATE_USD;
        }

        // 3. Determine target currency and its exchange rate from USD
        $clientUser = null;
        $targetCurrency = null;
        try {
            $clientUser       = !empty($project->user_id) ? User::find($project->user_id) : null;
            $targetCurrencyId = $options['currency_id'] ?? $clientUser?->currency_id;
            $targetCurrency   = $targetCurrencyId ? Currency::find($targetCurrencyId) : null;
        } catch (\Throwable $e) {
            $targetCurrency = null;
        }

        $exchangeRate   = 50.0;
        $currencySymbol = 'EGP';
        $currencyCode   = 'EGP';

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

        // Normalize input description text without duplicating $project->description
        $rawComponents = !empty($components) ? $components : ($project->ai_context['pending_features'] ?? []);
        if (empty($rawComponents) && !empty($project->project_name)) {
            $rawComponents = [$project->project_name];
        }

        $descriptionText = is_array($rawComponents) 
            ? implode(" \n", array_map(fn($c) => is_string($c) ? $c : json_encode($c), $rawComponents)) 
            : (string) $rawComponents;

        if (!empty($project->description) && !str_contains($descriptionText, trim($project->description))) {
            $descriptionText .= "\n" . $project->description;
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
        $techStack   = !empty($aiAnalysis['tech_stack']) ? $aiAnalysis['tech_stack'] : $platformInfo['tech_stack'];
        
        // Enforce tech_stack precision if a non-web platform was auto-detected and Gemini returned empty
        if ($platformInfo['platform'] !== 'Web' && empty($aiAnalysis['tech_stack'])) {
            $techStack = $platformInfo['tech_stack'];
        }

        $keyFeatures = $aiAnalysis['key_features'] ?? [];
        $selectedAiItems = $aiAnalysis['selected_components'] ?? null;

        $resolvedComponents = [];
        $sumComponentHours  = 0;

        if (!empty($selectedAiItems) && is_array($selectedAiItems)) {
            $seenTitles = [];
            foreach ($selectedAiItems as $item) {
                $key = strtolower(trim($item['key'] ?? ''));
                $customNameAr = trim($item['name_ar'] ?? '');
                $customDesc = $item['description_ar'] ?? null;

                // Deduplicate items with identical or visually duplicated titles
                $normalizedTitle = mb_strtolower(preg_replace('/\s+/u', ' ', $customNameAr));
                if (!empty($normalizedTitle) && isset($seenTitles[$normalizedTitle])) {
                    continue;
                }
                if (!empty($normalizedTitle)) {
                    $seenTitles[$normalizedTitle] = true;
                }

                if (!empty($key) && isset($registeredCatalog[$key])) {
                    $catComp = $registeredCatalog[$key];
                    $baseH = ($contextType === 'NEW_PROJECT') ? ($catComp['marginal_hours'] ?? 4) : ($catComp['standalone_hours'] ?? 3);
                    if (!empty($item['hours'])) {
                        $baseH = (int) max(1, $item['hours']);
                    }
                    $h = (int) max(1, round($baseH * $platformMultiplier));
                    $compCostUsd = round($h * $hourlyRate, 2);
                    $sumComponentHours += $h;

                    $resolvedComponents[] = [
                        'key'             => $key,
                        'name_ar'         => !empty($customNameAr) ? $customNameAr : $catComp['name_ar'],
                        'name_en'         => $catComp['name_en'],
                        'description_ar'  => $customDesc ?: 'بناء وتكامل الوحدة البرمجية المسجلة وفق معايير النظام.',
                        'complexity'      => strtolower($catComp['complexity'] ?? 'medium'),
                        'estimated_hours' => $h,
                        'cost_usd'        => $compCostUsd,
                    ];
                } elseif (!empty($customNameAr)) {
                    // Custom AI component not in catalog
                    $baseH = (int) max(1, $item['hours'] ?? 2);
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

        // Fallback Task Parser if AI returned empty or failed
        if (empty($resolvedComponents)) {
            $promptLines = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $cleanPromptText)));

            if (count($promptLines) >= 2) {
                $seenLines = [];
                foreach ($promptLines as $line) {
                    $cleanTitle = preg_replace('/^(?:\d+[\.\)]|[-*•])\s*/u', '', $line);
                    if (mb_strlen($cleanTitle) < 3) continue;

                    $normalizedLine = mb_strtolower($cleanTitle);
                    if (isset($seenLines[$normalizedLine])) continue;
                    $seenLines[$normalizedLine] = true;

                    $h = (int) max(1, round(2 * $platformMultiplier));
                    $compCostUsd = round($h * $hourlyRate, 2);
                    $sumComponentHours += $h;

                    $resolvedComponents[] = [
                        'key'             => 'task_' . md5($cleanTitle),
                        'name_ar'         => $cleanTitle,
                        'name_en'         => $cleanTitle,
                        'description_ar'  => 'تطوير وتنفيذ المهمة المطلوبة: ' . $cleanTitle,
                        'complexity'      => 'medium',
                        'estimated_hours' => $h,
                        'cost_usd'        => $compCostUsd,
                    ];
                }
            } else {
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
                            : ($catComp['standalone_hours'] ?? 3);

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
        }

        // Add mobile integration features to keyFeatures if mobile platform requested
        if (str_contains($platformInfo['platform'], 'Android') || str_contains($platformInfo['platform'], 'Flutter') || str_contains($platformInfo['platform'], 'iOS')) {
            array_unshift($keyFeatures, 'تطوير وتكامل تطبيق الهواتف الجوالة (' . $platformInfo['label_ar'] . ')');
            if (!in_array('بناء وتأمين بروتوكولات REST API للتطبيق', $keyFeatures)) {
                $keyFeatures[] = 'بناء وتأمين بروتوكولات REST API للتطبيق';
            }
        }

        if (empty($aiSummary)) {
            $aiSummary = 'تم تحليل نطاق مشروع ' . $platformInfo['label_ar'] . ' بنجاح وتفكيك المتطلبات إلى ' . count($resolvedComponents) . ' مكونات برمجية مسجلة بالنظام مع التجهيز التشغيلي.';
        }

        if (empty($keyFeatures)) {
            $keyFeatures = array_column($resolvedComponents, 'name_ar');
        }
        $keyFeatures = array_values(array_unique(array_filter($keyFeatures)));

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
     * Detect platform requirements (Android, Flutter, iOS, Bot, Web) and calculate platform multiplier.
     */
    protected function detectPlatformInfo(string $prompt): array
    {
        $text = mb_strtolower($prompt);

        $hasBot     = str_contains($text, 'bot') || str_contains($text, 'بوت') || str_contains($text, 'telegram') || str_contains($text, 'تلجرام') || str_contains($text, 'whatsapp') || str_contains($text, 'واتساب') || str_contains($text, 'otpjob');
        $hasScript  = str_contains($text, 'script') || str_contains($text, 'سكربت') || str_contains($text, 'automation') || str_contains($text, 'أوتوميشن') || str_contains($text, 'handler');
        $hasAndroid = str_contains($text, 'android') || str_contains($text, 'اندرويد') || str_contains($text, 'أندرويد');
        $hasFlutter = str_contains($text, 'flutter') || str_contains($text, 'فلاتر');
        $hasIos     = str_contains($text, 'ios') || str_contains($text, 'ايفون') || str_contains($text, 'آيفون') || str_contains($text, 'swift');
        $hasMobile  = str_contains($text, 'mobile') || str_contains($text, 'موبايل') || str_contains($text, 'تطبيق');
        $hasWeb     = str_contains($text, 'web') || str_contains($text, 'موقع') || str_contains($text, 'react') || str_contains($text, 'laravel');

        if (($hasBot || $hasScript) && !$hasAndroid && !$hasFlutter && !$hasIos) {
            return [
                'platform'   => 'Bot & Script Automation',
                'tech_stack' => 'Telegram / Bot API / Script Modifications',
                'multiplier' => 0.75,
                'label_ar'   => 'تعديلات وإضافات بوت / أوتوميشن (Bot & Script Modifications)',
            ];
        }

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
        $apiKeysString = null;
        $model = 'gemini-2.0-flash';
        try {
            $apiKeysString = AdminSettings::GetValue('gemini_api_keys')
                ?: AdminSettings::GetValue('gemini_api_key')
                ?: (auth()->check() ? auth()->user()?->gemini_api : null)
                ?: config('services.gemini.key');
            $model = AdminSettings::GetValue('gemini_model', 'gemini-2.0-flash');
        } catch (\Throwable $e) {
            $apiKeysString = config('services.gemini.key');
        }

        if (empty($apiKeysString)) {
            return null;
        }

        $keys = array_filter(array_map('trim', explode(',', (string) $apiKeysString)));
        if (empty($keys)) {
            return null;
        }

        $apiKey = $keys[0];

        $catalogList = [];
        foreach ($catalog as $key => $c) {
            $catalogList[] = [
                'key'     => $key,
                'name_ar' => $c['name_ar'],
                'name_en' => $c['name_en'],
            ];
        }

        $prompt = "You are an expert enterprise software engineering solution architect and estimator.\n"
            . "User Requirements Prompt:\n\"{$description}\"\n\n"
            . "OFFICIAL REGISTERED SYSTEM COMPONENTS CATALOG (JSON):\n" . json_encode($catalogList, JSON_UNESCAPED_UNICODE) . "\n\n"
            . "Instructions:\n"
            . "1. Analyze the user prompt carefully line by line. DO NOT duplicate requirements or output repeated components!\n"
            . "2. Detect if the user is asking for minor Bot Updates / Script Modifications (e.g. adding bot buttons, updating handlers, sum/withdraw commands, changing manager ID) vs a full web application build.\n"
            . "3. For minor Bot / Script updates, set 'tech_stack' to 'Telegram / Bot API / Script Modifications' (or matching specific bot tech), keep estimated development hours lean (1 to 3 hours per micro-component), and do NOT inflate scope.\n"
            . "4. For each micro-component, provide a specific, clear Arabic title ('name_ar') describing the specific task (e.g. 'إضافة أزرار حسابات OTPJob: Refresh/Withdraw/Sum', 'تحديث زر الموظفين وإظهار اسم البوت'). DO NOT use generic template names!\n"
            . "5. Map each task to the closest catalog key in 'key' if applicable, or specify 'key': 'custom'.\n"
            . "6. Provide a concise description in Arabic ('description_ar') and estimated development hours ('hours', e.g. 1 to 3 hours for bot tweaks, 2 to 6 hours for complex features) for each micro-component.\n"
            . "7. Provide an executive summary in Arabic ('ai_summary') and a UNIQUE key deliverables list ('key_features') matching the user's tasks.\n\n"
            . "Respond strictly with a JSON object matching this structure (no markdown wrappers):\n"
            . "{\n"
            . "  \"ai_summary\": \"ملخص تنفيذي للمشروع والمهام المطلوبة باللغة العربية...\",\n"
            . "  \"tech_stack\": \"Telegram / Bot API / Script Modifications\",\n"
            . "  \"key_features\": [\"ميزة 1\", \"ميزة 2\"],\n"
            . "  \"selected_components\": [\n"
            . "    {\n"
            . "      \"key\": \"matching_catalog_key_or_custom\",\n"
            . "      \"name_ar\": \"عنوان عربي مخصص يوضح المهمة المطلوبة بدقة\",\n"
            . "      \"description_ar\": \"شرح مختصر باللغة العربية لما سيتم إنجازه لهذا البند...\",\n"
            . "      \"hours\": 2\n"
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
        $text = mb_strtolower(
            implode(' ', array_map(fn($c) => is_array($c) ? ($c['name'] ?? $c['name_ar'] ?? '') : (string) $c, $components))
            . ' ' . ($project->project_name ?? '')
            . ' ' . ($project->description ?? '')
        );

        if (str_contains($text, 'fix') || str_contains($text, 'bug') || str_contains($text, 'خطأ') || str_contains($text, 'إصلاح') || str_contains($text, 'مشكلة')) {
            return 'BUG_FIX';
        }

        // Minor updates / script tweaks / bot modifications should always be EXISTING_PROJECT_FEATURE
        $isScriptOrBot = str_contains($text, 'bot') || str_contains($text, 'بوت') || str_contains($text, 'script') || str_contains($text, 'سكربت')
            || str_contains($text, 'tweak') || str_contains($text, 'update') || str_contains($text, 'تعديل') || str_contains($text, 'تحديث')
            || str_contains($text, 'أزرار') || str_contains($text, 'زر') || str_contains($text, 'otpjob');

        if (!empty($project->approved_scope) || count($components) <= 2 || $isScriptOrBot) {
            return 'EXISTING_PROJECT_FEATURE';
        }

        return 'NEW_PROJECT';
    }
}
