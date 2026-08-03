<?php

namespace App\Services\AI;

use App\Models\Project;

class AiAgencyValuationService
{
    protected ScopePricingEngine $pricingEngine;

    public function __construct()
    {
        $this->pricingEngine = new ScopePricingEngine();
    }

    /**
     * Calculate market valuation for a project using ScopePricingEngine.
     */
    public function evaluateProject(Project $project, array $features = []): array
    {
        return $this->pricingEngine->calculateValuation($project, $features);
    }

    /**
     * Propose Scope Negotiation options when client requests budget reduction.
     */
    public function negotiateScope(Project $project, float $requestedBudget): array
    {
        $currentValuation = $this->evaluateProject($project, $project->ai_context['pending_features'] ?? []);
        $currentCost      = $currentValuation['converted_amount'];

        if ($requestedBudget >= $currentCost) {
            return [
                'status'  => 'accepted',
                'message' => 'Budget proposal accepted without scope adjustments.',
            ];
        }

        $features        = $project->ai_context['pending_features'] ?? [];
        $dropCount       = max(1, (int) ceil(count($features) * 0.3));
        $featuresToDefer = array_slice($features, -$dropCount);

        return [
            'status'           => 'negotiation_proposed',
            'requested_budget' => $requestedBudget,
            'original_cost'    => $currentCost,
            'currency_symbol'  => $currentValuation['currency_symbol'],
            'proposals'        => [
                [
                    'option'   => 'Phase 1 MVP Release',
                    'desc'     => 'Focus Phase 1 on core features and defer non-essential features (' . implode(', ', $featuresToDefer) . ') to Phase 2.',
                    'new_cost' => round($currentCost * 0.7, 2),
                ],
                [
                    'option'   => 'Standard Framework Alternative',
                    'desc'     => 'Use pre-built UI components and standard modules to reduce total development hours.',
                    'new_cost' => round($currentCost * 0.8, 2),
                ],
            ],
        ];
    }
}
