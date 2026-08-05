<?php

namespace App\Services\AI;

use App\Helpers\FinanceHelper;
use App\Models\AdminSettings;
use App\Models\ContractPriceItem;
use App\Models\CurrenciesExchange;
use App\Models\Currency;
use App\Models\Project;
use App\Models\User;
use App\Traits\ConvertsCurrency;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ScopePricingEngine
{
    use ConvertsCurrency;
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

        // 1. Resolve USD and EGP models using cached helper to determine USD -> EGP rate
        $usdId = 1;
        $egpId = 2;
        $usdCurrency = null;
        $egpCurrency = null;

        try {
            $usdCurrency = Currency::findCached(1) ?? Currency::where('currency', 'USD')->first();
            $egpCurrency = Currency::findCached(2) ?? Currency::where('currency', 'EGP')->first();
            $usdId = $usdCurrency?->id ?? 1;
            $egpId = $egpCurrency?->id ?? 2;
        } catch (\Throwable $e) {
            // DB offline or unmigrated in unit tests
        }

        $usdToEgpRate = 50.0;
        try {
            $rate = CurrenciesExchange::RateToday(1.0, $usdId, $egpId);
            if ($rate > 0) {
                $usdToEgpRate = (float) $rate;
            } elseif ($egpCurrency?->rate > 0) {
                $usdToEgpRate = (float) $egpCurrency->rate;
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

        // 3. Determine target currency object and exchange rate using system Helpers
        $clientUser = null;
        $targetCurrency = null;
        try {
            $clientUser       = !empty($project->user_id) ? User::find($project->user_id) : null;
            $targetCurrencyId = $options['currency_id'] ?? $clientUser?->currency_id ?? $egpId;
            $targetCurrency   = Currency::findCached($targetCurrencyId) ?? Currency::find($targetCurrencyId);
        } catch (\Throwable $e) {
            $targetCurrency = null;
        }

        $exchangeRate   = 1.0;
        $currencySymbol = '$';
        $currencyCode   = 'USD';

        if ($targetCurrency) {
            $currencySymbol = $targetCurrency->symbol ?? $targetCurrency->currency;
            $currencyCode   = $targetCurrency->currency;

            if ($targetCurrency->id !== $usdId) {
                try {
                    $convertedRate = CurrenciesExchange::RateToday(1.0, $usdId, $targetCurrency->id);
                    if ($convertedRate > 0) {
                        $exchangeRate = (float) $convertedRate;
                    } elseif ($targetCurrency->rate > 0) {
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

        $selectedAnswer = $options['selected_answer'] ?? null;
        if (!empty($selectedAnswer)) {
            $descriptionText .= "\n\nتوضيح العميل المحدد لنطاق العمل: " . $selectedAnswer;
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

        $needsClarification = empty($selectedAnswer) && !empty($aiAnalysis['needs_clarification']);
        $clarifyingQuestion = $aiAnalysis['clarifying_question'] ?? null;
        $suggestedAnswers   = is_array($aiAnalysis['suggested_answers'] ?? null) ? $aiAnalysis['suggested_answers'] : [];

        // Fallback ambiguity detection if prompt is brief (<= 25 chars) and no clarification generated yet
        if (empty($selectedAnswer) && !$needsClarification && mb_strlen($cleanPromptText) <= 30 && count($suggestedAnswers) < 3) {
            $needsClarification = true;
            $clarifyingQuestion = "ما هو النطاق التكنيكي المفضل لتنفيذ '{$cleanPromptText}'؟";
            $suggestedAnswers = [
                "تجهيز وإعداد المفاتيح والتهيئة الأساسية (Setup & Configuration)",
                "بناء الخدمة والربط البرمجي المتكامل مع لوحة التحكم (Full System Integration)",
                "فحص وإصلاح المشاكل الفنية الحالية للتطبيق (Troubleshooting & Fix)"
            ];
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
            $textLower = mb_strtolower($cleanPromptText);
            $isSecurityOrMalwarePrompt = str_contains($textLower, 'خبيثة') || str_contains($textLower, 'فيروس') || str_contains($textLower, 'اختراق') || str_contains($textLower, 'ثغرة') || str_contains($textLower, 'malware') || str_contains($textLower, 'clean');

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

                // Contextual Relevance Guard: Reject file_manager if prompt is security/malware and not media library
                if ($key === 'file_manager' && $isSecurityOrMalwarePrompt && !str_contains($textLower, 'ميديا') && !str_contains($textLower, 'وسائط')) {
                    $key = 'custom';
                }

                if (!empty($key) && $key !== 'custom' && isset($registeredCatalog[$key])) {
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
                        'is_new_item'     => false,
                    ];
                } elseif (!empty($customNameAr)) {
                    // Custom AI component not in standard catalog — Auto register as dynamic ContractPriceItem
                    $baseH = (int) max(1, $item['hours'] ?? 2);
                    $h = (int) max(1, round($baseH * $platformMultiplier));
                    $compCostUsd = round($h * $hourlyRate, 2);
                    $sumComponentHours += $h;

                    $itemKey = 'custom_' . Str::slug($item['name_en'] ?? $customNameAr, '_');
                    if (strlen($itemKey) < 8) {
                        $itemKey = 'custom_' . substr(md5($customNameAr), 0, 8);
                    }

                    $newPriceItem = $this->autoRegisterContractPriceItem($itemKey, $customNameAr, $customDesc, $h);

                    $resolvedComponents[] = [
                        'key'                    => $itemKey,
                        'name_ar'                => $customNameAr,
                        'name_en'                => $item['name_en'] ?? 'Custom Feature',
                        'description_ar'         => $customDesc ?: 'تطوير وتنفيذ الخدمة المخصصة وفق متطلبات العميل.',
                        'complexity'             => strtolower($item['complexity'] ?? 'medium'),
                        'estimated_hours'        => $h,
                        'cost_usd'               => $compCostUsd,
                        'is_new_item'            => true,
                        'contract_price_item_id' => $newPriceItem?->id,
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
            'needs_clarification'  => $needsClarification,
            'clarifying_question'  => $clarifyingQuestion,
            'suggested_answers'    => array_values($suggestedAnswers),
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
     * Automatically persist custom component as a general reusable ContractPriceItem in the DB catalog.
     */
    protected function autoRegisterContractPriceItem(string $key, string $nameAr, ?string $descriptionAr, int $hours): ?ContractPriceItem
    {
        try {
            // Generalize title: Strip domain names, URLs, or client-specific references
            $cleanNameAr = preg_replace('/https?:\/\/\S+|www\.\S+|\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}\b|\b[a-zA-Z0-9-]+\.(com|net|org|eg|io|co)\b/u', '', $nameAr);
            $cleanNameAr = trim(preg_replace('/\s+/u', ' ', $cleanNameAr));

            if (mb_strlen($cleanNameAr) < 3) {
                $cleanNameAr = $nameAr;
            }

            return ContractPriceItem::firstOrCreate(
                ['name_ar' => $cleanNameAr],
                [
                    'key'              => $key,
                    'name'             => $cleanNameAr,
                    'name_en'          => $cleanNameAr,
                    'description'      => $descriptionAr ?: 'بند تسعير عام مسجل بالمكونات القياسية للنظام.',
                    'standalone_hours' => $hours,
                    'marginal_hours'   => max(1, (int) round($hours * 0.6)),
                    'complexity'       => 'medium',
                    'keywords'         => array_values(array_filter(explode(' ', $cleanNameAr))),
                    'is_active'        => true,
                    'sort_order'       => 99,
                ]
            );
        } catch (\Throwable $e) {
            Log::warning('Failed to auto register ContractPriceItem: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Detect platform requirements (Android, Flutter, iOS, Security, Server, Bot, Web) and calculate platform multiplier.
     */
    protected function detectPlatformInfo(string $prompt): array
    {
        $text = mb_strtolower($prompt);

        $hasSecurity = str_contains($text, 'خبيثة') || str_contains($text, 'ملفات خبيثة') || str_contains($text, 'فيروس') || str_contains($text, 'اختراق') || str_contains($text, 'ثغرة') || str_contains($text, 'تنظيف') || str_contains($text, 'هوستينجر') || str_contains($text, 'hostinger') || str_contains($text, 'malware') || str_contains($text, 'clean');
        $hasServer   = str_contains($text, 'سيرفر') || str_contains($text, 'cpanel') || str_contains($text, 'vps') || str_contains($text, 'دومين') || str_contains($text, 'استضافة') || str_contains($text, 'server') || str_contains($text, 'hosting');
        $hasBot      = str_contains($text, 'bot') || str_contains($text, 'بوت') || str_contains($text, 'telegram') || str_contains($text, 'تلجرام') || str_contains($text, 'whatsapp') || str_contains($text, 'واتساب') || str_contains($text, 'otpjob');
        $hasScript   = str_contains($text, 'script') || str_contains($text, 'سكربت') || str_contains($text, 'automation') || str_contains($text, 'أوتوميشن') || str_contains($text, 'handler');
        $hasAndroid  = str_contains($text, 'android') || str_contains($text, 'اندرويد') || str_contains($text, 'أندرويد');
        $hasFlutter  = str_contains($text, 'flutter') || str_contains($text, 'فلاتر');
        $hasIos      = str_contains($text, 'ios') || str_contains($text, 'ايفون') || str_contains($text, 'آيفون') || str_contains($text, 'swift');
        $hasMobile   = str_contains($text, 'mobile') || str_contains($text, 'موبايل') || str_contains($text, 'تطبيق');
        $hasWeb      = str_contains($text, 'web') || str_contains($text, 'موقع') || str_contains($text, 'react') || str_contains($text, 'laravel');

        if ($hasSecurity) {
            return [
                'platform'   => 'Security Audit & Hosting Maintenance',
                'tech_stack' => 'Hostinger Security Audit / Malware Cleanup & Server Hardening',
                'multiplier' => 0.8,
                'label_ar'   => 'خدمة فحص وتأمين الاستضافة وتنظيف الملفات الخبيثة (Security Audit & Hosting Maintenance)',
            ];
        }

        if ($hasServer && !$hasWeb && !$hasAndroid && !$hasFlutter) {
            return [
                'platform'   => 'Server & Hosting Operations',
                'tech_stack' => 'Linux Server Administration / Hosting Setup',
                'multiplier' => 0.85,
                'label_ar'   => 'إدارة وتجهيز الاستضافة والسيرفر (Server & Hosting Operations)',
            ];
        }

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

        $isSecurityOrMalware = str_contains($normalizedText, 'خبيثة') || str_contains($normalizedText, 'فيروس') || str_contains($normalizedText, 'اختراق') || str_contains($normalizedText, 'ثغرة') || str_contains($normalizedText, 'malware');

        foreach ($catalog as $key => $comp) {
            // Prevent 'file_manager' matching when user text is about malware/security
            if ($key === 'file_manager' && $isSecurityOrMalware && !str_contains($normalizedText, 'ميديا') && !str_contains($normalizedText, 'وسائط')) {
                continue;
            }

            $keywords = is_array($comp['keywords']) ? $comp['keywords'] : [];
            
            // Check if any keyword matches the user prompt
            foreach ($keywords as $kw) {
                if (!empty($kw) && str_contains($normalizedText, mb_strtolower($kw))) {
                    $selectedKeys[] = $key;
                    break;
                }
            }
        }

        // Always ensure essential default components for web apps if prompt explicitly implies a full web app build
        $isFullWebApp = (str_contains($normalizedText, 'موقع') || str_contains($normalizedText, 'تطبيق') || str_contains($normalizedText, 'متجر') || str_contains($normalizedText, 'منصة')) && !$isSecurityOrMalware;

        if ($isFullWebApp) {
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
            . "2. STRICT RELEVANCE CHECK: Review all catalog items strictly against the user's prompt text. DO NOT map irrelevant catalog components! For example, if the prompt mentions 'ملفات خبيثة' (malware files), DO NOT select 'File & Media Manager' ('file_manager')! Only select catalog items if they directly match the user's problem.\n"
            . "3. DYNAMIC GENERAL CONTRACT PRICE LIST ITEMS: If the user prompt describes tasks not in the catalog (e.g. malware cleanup, security audit, server fix, custom bot tweak), DO NOT force-fit generic web app components. Create new custom micro-components with key set to 'custom' (or 'custom_...'), a clear, GENERAL, professional Arabic name ('name_ar'), a clean English name ('name_en'), description ('description_ar'), and realistic development hours ('hours'). IMPORTANT: The titles MUST be GENERAL and reusable for any client (e.g. 'فحص واستخراج الملفات الخبيثة وتأمين الاستضافة'). DO NOT include specific client names, personal email addresses, domain names, or private credentials in the title!\n"
            . "4. For minor Bot / Security / Script / Maintenance tasks, set 'tech_stack' to match the actual tech (e.g., 'Hostinger Security Audit / Malware Cleanup' or 'Telegram Bot API'), keep estimated development hours lean (1 to 4 hours per micro-component), and do NOT inflate scope.\n"
            . "5. For each micro-component, provide a specific, clear Arabic title ('name_ar') describing the exact task. DO NOT use generic template names!\n"
            . "6. Provide a concise description in Arabic ('description_ar') and estimated development hours ('hours') for each micro-component.\n"
            . "7. Provide an executive summary in Arabic ('ai_summary') and a UNIQUE key deliverables list ('key_features') matching the user's tasks.\n"
            . "8. AMBIGUITY & CLARIFICATION DETECTION: If the user requirements prompt is brief, underspecified, or open to multiple technical interpretations (e.g. 'إعداد Firebase Push', 'ربط الدفع', 'عمل تطبيق'), set 'needs_clarification': true, and provide 'clarifying_question' (a brief Arabic question) and 'suggested_answers' (an array of EXACTLY 3 distinct, specific Arabic technical options for the user to pick from). Otherwise, set 'needs_clarification': false.\n\n"
            . "Respond strictly with a JSON object matching this structure (no markdown wrappers):\n"
            . "{\n"
            . "  \"needs_clarification\": true,\n"
            . "  \"clarifying_question\": \"ما هو النطاق المحدد المطلوب لإعداد إشعارات Firebase Push؟\",\n"
            . "  \"suggested_answers\": [\n"
            . "    \"إعادة ضبط مفاتيح Firebase Service Account واستقبال الإشعارات بالتطبيق الحالي\",\n"
            . "    \"بناء سيرفر إشعارات متكامل لارسال الإشعارات الجماعية والفردية من لوحة التحكم\",\n"
            . "    \"فحص وإصلاح مشكلة عدم وصول الإشعارات بالتطبيق الحالي (Troubleshooting & Fix)\"\n"
            . "  ],\n"
            . "  \"ai_summary\": \"ملخص تنفيذي للمشروع والمهام المطلوبة باللغة العربية...\",\n"
            . "  \"tech_stack\": \"Hostinger Security Audit / Malware Cleanup & Server Hardening\",\n"
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

        $securityOrBugKeywords = [
            'fix', 'bug', 'خطأ', 'إصلاح', 'مشكلة', 'خبيثة', 'ملفات خبيثة', 'فيروس', 'فيروسات',
            'اختراق', 'ثغرة', 'ثغرات', 'هوست', 'هوستينجر', 'استضافة', 'احذفها', 'تنظيف', 'تأمين',
            'سيرفر', 'هجوم', 'تشفير', 'انترسبت', 'malware', 'clean', 'hack', 'virus', 'security',
            'hosting', 'hostinger', 'cpanel', 'vps'
        ];

        foreach ($securityOrBugKeywords as $kw) {
            if (str_contains($text, $kw)) {
                return 'BUG_FIX';
            }
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
