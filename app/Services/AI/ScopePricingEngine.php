<?php

namespace App\Services\AI;

use App\Models\CurrenciesExchange;
use App\Models\Currency;
use App\Models\Project;
use App\Models\User;

class ScopePricingEngine
{
    /**
     * Calculate comprehensive scope-based valuation for a project.
     */
    public function calculateValuation(Project $project, array $features = [], ?string $customText = null): array
    {
        $benchmarks = EgyptianMarketBenchmarkRates::getBenchmarks();

        $textToAnalyze = mb_strtolower(
            ($customText ?? '') . ' ' .
            ($project->project_name ?? '') . ' ' .
            ($project->description ?? '') . ' ' .
            implode(' ', $features)
        );

        // Detect archetype
        $typeKey = $this->detectArchetype($textToAnalyze);
        $benchmark = $benchmarks[$typeKey] ?? $benchmarks['corporate_website'];

        $baseUsd = (float) $benchmark['base_usd'];
        $minUsd  = (float) ($benchmark['min_usd'] ?? ($baseUsd * 0.7));
        $maxUsd  = (float) ($benchmark['max_usd'] ?? ($baseUsd * 1.5));
        $estDays = (int) $benchmark['est_days'];

        // Adjust for feature count & complexity multiplier
        $featCount = max(1, count($features));
        $complexityMultiplier = 1.0 + min(1.5, ($featCount * 0.1));

        $recommendedUsd = round($baseUsd * $complexityMultiplier, 2);
        $finalMinUsd    = round($minUsd * $complexityMultiplier, 2);
        $finalMaxUsd    = round($maxUsd * $complexityMultiplier, 2);
        $calculatedDays = ceil($estDays * $complexityMultiplier);

        // Convert USD to Client's Currency (EGP or user currency)
        $clientUser       = !empty($project->user_id) ? User::find($project->user_id) : null;
        $targetCurrencyId = $clientUser?->currency_id;
        try {
            $usdCurrency = Currency::where('currency', 'USD')->first();
        } catch (\Throwable $e) {
            $usdCurrency = null;
        }

        $convertedAmount = $recommendedUsd;
        $currencySymbol = '$';

        if ($targetCurrencyId && $usdCurrency && $targetCurrencyId !== $usdCurrency->id) {
            $convertedAmount = CurrenciesExchange::RateToday($recommendedUsd, $usdCurrency->id, $targetCurrencyId);
            $targetCurrency = Currency::find($targetCurrencyId);
            $currencySymbol = $targetCurrency?->symbol ?? 'EGP';
        }

        // Build feature cost breakdown
        $breakdown = $this->buildFeatureBreakdown($features, $recommendedUsd);

        return [
            'type_key'          => $typeKey,
            'type_name_ar'      => $benchmark['name_ar'],
            'type_name_en'      => $benchmark['name_en'],
            'min_usd'           => $finalMinUsd,
            'max_usd'           => $finalMaxUsd,
            'recommended_usd'   => $recommendedUsd,
            'converted_amount'  => round($convertedAmount, 2),
            'currency_symbol'   => $currencySymbol,
            'estimated_days'    => $calculatedDays,
            'complexity'        => $featCount > 8 ? 'High' : ($featCount > 4 ? 'Medium' : 'Standard'),
            'feature_breakdown' => $breakdown,
        ];
    }

    /**
     * Detect project archetype cleanly from text.
     */
    protected function detectArchetype(string $text): string
    {
        if (
            str_contains($text, 'todo') || str_contains($text, 'to-do') ||
            str_contains($text, 'قائمة مهام') || str_contains($text, 'مهام بسيطة') ||
            str_contains($text, 'crud') ||
            (str_contains($text, 'تطبيق') && (str_contains($text, 'بسيط') || str_contains($text, 'صغير') || str_contains($text, 'تدريبي')))
        ) {
            return 'todo_simple_crud';
        }

        if (str_contains($text, 'متجر') || str_contains($text, 'e-commerce') || str_contains($text, 'store') || str_contains($text, 'بيع')) {
            return 'ecommerce_store';
        }

        if (
            str_contains($text, 'mobile app') || str_contains($text, 'تطبيق موبايل') ||
            str_contains($text, 'android') || str_contains($text, 'ios') || str_contains($text, 'اندرويد') ||
            (str_contains($text, 'تطبيق') && (str_contains($text, 'ايفون') || str_contains($text, 'جوال')))
        ) {
            return 'mobile_application';
        }

        if (str_contains($text, 'crm') || str_contains($text, 'إدارة عملاء') || str_contains($text, 'علاقات عملاء')) {
            return 'crm_system';
        }

        if (str_contains($text, 'erp') || str_contains($text, 'حسابات') || str_contains($text, 'مخازن') || str_contains($text, 'موارد بشرية')) {
            return 'erp_system';
        }

        if (str_contains($text, 'هبوط') || str_contains($text, 'landing')) {
            return 'landing_page';
        }

        if (str_contains($text, 'داشبورد') || str_contains($text, 'dashboard') || str_contains($text, 'لوحة تحكم')) {
            return 'admin_dashboard';
        }

        if (str_contains($text, 'web app') || str_contains($text, 'تطبيق ويب') || str_contains($text, 'mvp')) {
            return 'mvp_web_app';
        }

        return 'corporate_website';
    }

    /**
     * Itemize cost breakdown by features.
     */
    protected function buildFeatureBreakdown(array $features, float $totalUsd): array
    {
        if (empty($features)) {
            return [
                ['name' => 'تطوير النواة الأساسية وقواعد البيانات', 'estimated_usd' => round($totalUsd * 0.6, 2)],
                ['name' => 'واجهة المستخدم واختبارات الأداء', 'estimated_usd' => round($totalUsd * 0.4, 2)],
            ];
        }

        $count = count($features);
        $share = round($totalUsd / $count, 2);

        $breakdown = [];
        foreach ($features as $f) {
            $breakdown[] = [
                'name'          => $f,
                'estimated_usd' => $share,
            ];
        }

        return $breakdown;
    }
}
