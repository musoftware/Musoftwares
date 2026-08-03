<?php

namespace App\Services\AI;

use App\Models\CurrenciesExchange;
use App\Models\Currency;
use App\Models\Project;
use App\Models\User;

class EstimationAgentService
{
    /**
     * Generate a full granular component-based estimation breakdown for a project requirement.
     */
    public function generateGranularEstimation(Project $project, array $features = [], ?string $archetype = null, float $totalUsd = 0.0): array
    {
        $pricingEngine = new ScopePricingEngine();
        $valuation = $pricingEngine->calculateValuation($project, $features);

        return [
            'type_key'             => 'component_based',
            'type_name_ar'         => 'تسعير قائم على المكونات (Component-Based)',
            'type_name_en'         => 'Component-Based Valuation',
            'total_usd'            => $valuation['recommended_usd'],
            'total_converted'      => $valuation['converted_amount'],
            'currency_symbol'      => $valuation['currency_symbol'],
            'total_hours'          => $valuation['total_hours'],
            'estimated_days'       => $valuation['estimated_days'],
            'components_count'     => count($valuation['micro_components']),
            'micro_components'     => $valuation['micro_components'],
        ];
    }
}
