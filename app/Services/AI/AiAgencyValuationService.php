<?php

namespace App\Services\AI;

use App\Models\Currency;
use App\Models\CurrenciesExchange;
use App\Models\Project;
use App\Models\User;

class AiAgencyValuationService
{
    /**
     * Calculate market valuation for a project in USD and client's target currency.
     */
    public function evaluateProject(Project $project, array $features = []): array
    {
        $benchmarks = EgyptianMarketBenchmarkRates::getBenchmarks();

        // 1. Detect project archetype
        $text = mb_strtolower($project->project_name . ' ' . implode(' ', $features));
        $typeKey = 'corporate_website';

        if (str_contains($text, 'متجر') || str_contains($text, 'e-commerce') || str_contains($text, 'store') || str_contains($text, 'بيع')) {
            $typeKey = 'ecommerce_store';
        } elseif (str_contains($text, 'تطبيق') || str_contains($text, 'mobile') || str_contains($text, 'app') || str_contains($text, 'اندرويد')) {
            $typeKey = 'mobile_application';
        } elseif (str_contains($text, 'crm') || str_contains($text, 'عملاء')) {
            $typeKey = 'crm_system';
        } elseif (str_contains($text, 'erp') || str_contains($text, 'حسابات') || str_contains($text, 'مخازن')) {
            $typeKey = 'erp_system';
        } elseif (str_contains($text, 'هبوط') || str_contains($text, 'landing')) {
            $typeKey = 'landing_page';
        } elseif (str_contains($text, 'داشبورد') || str_contains($text, 'dashboard')) {
            $typeKey = 'admin_dashboard';
        }

        $benchmark = $benchmarks[$typeKey] ?? $benchmarks['corporate_website'];
        $baseUsd = $benchmark['base_usd'];

        // 2. Adjust for feature count & complexity
        $featCount = count($features);
        $complexityMultiplier = 1.0 + min(1.5, ($featCount * 0.1));
        $totalUsd = round($baseUsd * $complexityMultiplier, 2);

        // 3. Convert USD to Client Currency (EGP or active user currency)
        $clientUser = User::find($project->user_id);
        $targetCurrencyId = $clientUser?->currency_id;
        $usdCurrency = Currency::where('currency', 'USD')->first();

        $convertedAmount = $totalUsd;
        $currencySymbol = '$';

        if ($targetCurrencyId && $usdCurrency && $targetCurrencyId !== $usdCurrency->id) {
            $convertedAmount = CurrenciesExchange::RateToday($totalUsd, $usdCurrency->id, $targetCurrencyId);
            $targetCurrency = Currency::find($targetCurrencyId);
            $currencySymbol = $targetCurrency?->symbol ?? 'EGP';
        }

        return [
            'type_key'          => $typeKey,
            'type_name_ar'      => $benchmark['name_ar'],
            'type_name_en'      => $benchmark['name_en'],
            'total_usd'         => $totalUsd,
            'converted_amount'  => round($convertedAmount, 2),
            'currency_symbol'   => $currencySymbol,
            'estimated_days'    => ceil($benchmark['est_days'] * $complexityMultiplier),
            'complexity'        => $featCount > 10 ? 'High' : ($featCount > 5 ? 'Medium' : 'Standard'),
        ];
    }

    /**
     * Propose Scope Negotiation options when client requests budget reduction.
     */
    public function negotiateScope(Project $project, float $requestedBudget): array
    {
        $currentValuation = $this->evaluateProject($project, $project->ai_summary['features'] ?? []);
        $currentCost = $currentValuation['converted_amount'];

        if ($requestedBudget >= $currentCost) {
            return [
                'status'  => 'accepted',
                'message' => 'Budget proposal accepted without scope adjustments.',
            ];
        }

        $features = $project->ai_summary['features'] ?? [];
        $dropCount = max(1, ceil(count($features) * 0.3));
        $featuresToDefer = array_slice($features, -$dropCount);
        $phase1Features  = array_slice($features, 0, count($features) - $dropCount);

        return [
            'status'             => 'negotiation_proposed',
            'requested_budget'   => $requestedBudget,
            'original_cost'      => $currentCost,
            'currency_symbol'    => $currentValuation['currency_symbol'],
            'proposals'          => [
                [
                    'option' => 'Phase 1 MVP Release',
                    'desc'   => 'Focus Phase 1 on core features and defer non-essential features (' . implode(', ', $featuresToDefer) . ') to Phase 2.',
                    'new_cost' => round($currentCost * 0.7, 2),
                ],
                [
                    'option' => 'Standard Framework Alternative',
                    'desc'   => 'Use pre-built UI components and standard modules to reduce total development hours.',
                    'new_cost' => round($currentCost * 0.8, 2),
                ],
            ],
        ];
    }
}
