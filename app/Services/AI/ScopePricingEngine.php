<?php

namespace App\Services\AI;

use App\Models\CurrenciesExchange;
use App\Models\Currency;
use App\Models\Project;
use App\Models\User;

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
     * @param array $options Additional options (context_type, hourly_rate_usd).
     * @return array
     */
    public function calculateValuation(Project $project, array $components = [], array $options = []): array
    {
        $hourlyRate  = (float) ($options['hourly_rate_usd'] ?? self::BASE_HOURLY_RATE_USD);
        $contextType = $options['context_type'] ?? $this->detectContextType($components, $project);

        // 1. Determine Project Overhead (Fixed Base Context Cost)
        $overheadHours = match ($contextType) {
            'NEW_PROJECT'              => 8, // Git, DB Architecture, Setup, Deploy, PM, QA
            'EXISTING_PROJECT_FEATURE' => 2, // Branching, Context Review, Integration Testing
            'BUG_FIX'                  => 1, // Diagnostics & Verification
            default                    => 4,
        };

        // Normalize input components
        $rawComponents = !empty($components) ? $components : ($project->ai_context['pending_features'] ?? []);
        if (empty($rawComponents) && !empty($project->project_name)) {
            $rawComponents = [$project->project_name];
        }

        $resolvedComponents = [];
        $sumComponentHours  = 0;
        $componentCount     = max(1, count($rawComponents));

        // Economy of Scale factor: In a NEW_PROJECT with multiple components, marginal cost per component scales down
        $scaleFactor = ($contextType === 'NEW_PROJECT' && $componentCount > 1)
            ? max(0.5, 1.0 - ($componentCount * 0.04))
            : 1.0;

        foreach ($rawComponents as $compInput) {
            $resolved = ComponentBenchmarkRates::resolveComponent($compInput);
            
            $complexityMultiplier = match (strtolower($resolved['complexity'] ?? 'medium')) {
                'low'    => 0.8,
                'high'   => 1.3,
                default  => 1.0,
            };

            $baseH = ($contextType === 'NEW_PROJECT')
                ? $resolved['marginal_hours']
                : $resolved['standalone_hours'];

            $calculatedHours = (int) max(1, round($baseH * $complexityMultiplier * $scaleFactor));
            $componentCostUsd = round($calculatedHours * $hourlyRate, 2);

            $sumComponentHours += $calculatedHours;

            $resolvedComponents[] = [
                'name_ar'         => $resolved['name_ar'],
                'name_en'         => $resolved['name_en'],
                'complexity'      => $resolved['complexity'],
                'estimated_hours' => $calculatedHours,
                'cost_usd'        => $componentCostUsd,
            ];
        }

        // Total hours = Overhead + Component Hours
        $totalHours = $overheadHours + $sumComponentHours;

        // Build itemized micro-components list including Project Overhead as the first item
        $overheadCostUsd = round($overheadHours * $hourlyRate, 2);
        $overheadTitleAr = match ($contextType) {
            'NEW_PROJECT'              => 'التكلفة الثابتة للمشروع (Project Setup, DB Schema, Git & PM)',
            'EXISTING_PROJECT_FEATURE' => 'مراجعة الكود والدمج المباشر (Context Review & Integration)',
            'BUG_FIX'                  => 'الفحص الفني والتأكيد الجوهري (Diagnostics & QA)',
            default                    => 'التكلفة التشغيلية للطلب (Operational Overhead)',
        };

        $itemizedComponents = array_merge([
            [
                'name_ar'         => $overheadTitleAr,
                'name_en'         => 'Project Operational Overhead',
                'complexity'      => 'standard',
                'estimated_hours' => $overheadHours,
                'cost_usd'        => $overheadCostUsd,
            ]
        ], $resolvedComponents);

        $recommendedUsd = round($totalHours * $hourlyRate, 2);
        $minUsd         = round($recommendedUsd * 0.85, 2);
        $maxUsd         = round($recommendedUsd * 1.25, 2);
        $calculatedDays = (int) max(1, ceil($totalHours / 6)); // 6 productive hours per developer day

        // Convert USD to Client's Currency (EGP or target currency)
        $clientUser       = !empty($project->user_id) ? User::find($project->user_id) : null;
        $targetCurrencyId = $clientUser?->currency_id;
        $usdCurrency      = null;

        try {
            $usdCurrency = Currency::where('currency', 'USD')->first();
        } catch (\Throwable $e) {
            $usdCurrency = null;
        }

        $convertedAmount = $recommendedUsd;
        $currencySymbol  = '$';
        $exchangeRate    = 1.0;

        if ($targetCurrencyId && $usdCurrency && $targetCurrencyId !== $usdCurrency->id) {
            try {
                $convertedRate = CurrenciesExchange::RateToday(1.0, $usdCurrency->id, $targetCurrencyId);
                if ($convertedRate > 0) {
                    $exchangeRate = $convertedRate;
                }
            } catch (\Throwable $e) {
                $exchangeRate = 50.0;
            }

            $targetCurrency  = Currency::find($targetCurrencyId);
            $currencySymbol  = $targetCurrency?->symbol ?? 'EGP';
            $convertedAmount = round($recommendedUsd * $exchangeRate, 2);
        }

        // Attach converted costs to itemized components
        foreach ($itemizedComponents as &$comp) {
            $comp['converted_cost']  = round($comp['cost_usd'] * $exchangeRate, 2);
            $comp['currency_symbol'] = $currencySymbol;
        }
        unset($comp);

        return [
            'context_type'         => $contextType,
            'type_key'             => 'component_based',
            'type_name_ar'         => 'تسعير ثنائي المستوى متوازن (Two-Level Pricing Engine)',
            'type_name_en'         => 'Two-Level Realistic Valuation',
            'overhead_hours'       => $overheadHours,
            'overhead_cost_usd'    => $overheadCostUsd,
            'min_usd'              => $minUsd,
            'max_usd'              => $maxUsd,
            'recommended_usd'      => $recommendedUsd,
            'converted_amount'     => $convertedAmount,
            'currency_symbol'      => $currencySymbol,
            'estimated_days'       => $calculatedDays,
            'total_hours'          => $totalHours,
            'complexity'           => $totalHours > 80 ? 'High' : ($totalHours > 30 ? 'Medium' : 'Standard'),
            'feature_breakdown'    => array_map(fn($c) => ['name' => $c['name_ar'], 'estimated_usd' => $c['cost_usd']], $itemizedComponents),
            'micro_components'     => $itemizedComponents,
            'granular_estimation'  => [
                'context_type'     => $contextType,
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
