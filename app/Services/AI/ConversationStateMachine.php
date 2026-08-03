<?php

namespace App\Services\AI;

use App\Models\Project;

class ConversationStateMachine
{
    public const STAGE_GREETING  = 'GREETING';
    public const STAGE_DISCOVERY = 'DISCOVERY';
    public const STAGE_VALUATION = 'VALUATION';
    public const STAGE_PROPOSAL  = 'PROPOSAL';
    public const STAGE_EXECUTION = 'EXECUTION';
    public const STAGE_COMPLETED = 'COMPLETED';

    public const MAX_QUESTION_ROUNDS = 3;

    /**
     * Resolve and validate current stage from project context.
     */
    public function getCurrentStage(Project $project): string
    {
        $context = $project->ai_context ?? [];
        $stage   = strtoupper(trim($context['current_stage'] ?? self::STAGE_GREETING));

        $validStages = [
            self::STAGE_GREETING,
            self::STAGE_DISCOVERY,
            self::STAGE_VALUATION,
            self::STAGE_PROPOSAL,
            self::STAGE_EXECUTION,
            self::STAGE_COMPLETED,
        ];

        return in_array($stage, $validStages, true) ? $stage : self::STAGE_GREETING;
    }

    /**
     * Check whether transition from current stage to proposed stage is permitted.
     */
    public function canTransition(Project $project, string $targetStage, array $requirementsAnalysis = []): bool
    {
        $currentStage = $this->getCurrentStage($project);
        $targetStage  = strtoupper(trim($targetStage));

        if ($currentStage === $targetStage) {
            return true;
        }

        $completenessScore = (int) ($requirementsAnalysis['completeness_score'] ?? 0);
        $isComplete        = (bool) ($requirementsAnalysis['is_complete'] ?? false);

        switch ($targetStage) {
            case self::STAGE_DISCOVERY:
                return in_array($currentStage, [self::STAGE_GREETING, self::STAGE_DISCOVERY], true);

            case self::STAGE_VALUATION:
                // Allowed if requirements completeness >= 60% OR client explicitly asked for pricing
                return in_array($currentStage, [self::STAGE_GREETING, self::STAGE_DISCOVERY, self::STAGE_VALUATION], true)
                    && ($completenessScore >= 60 || $isComplete);

            case self::STAGE_PROPOSAL:
                // Requires stage to be at least VALUATION or DISCOVERY with complete scope
                return in_array($currentStage, [self::STAGE_DISCOVERY, self::STAGE_VALUATION, self::STAGE_PROPOSAL], true)
                    && ($completenessScore >= 70 || $isComplete);

            case self::STAGE_EXECUTION:
                // Requires confirmed invoice in context or explicit invoice approval
                $context = $project->ai_context ?? [];
                $hasInvoice = !empty($context['current_invoice_id']) || !empty($context['invoice_confirmed']);
                return in_array($currentStage, [self::STAGE_PROPOSAL, self::STAGE_EXECUTION], true) && $hasInvoice;

            case self::STAGE_COMPLETED:
                return $currentStage === self::STAGE_EXECUTION;

            default:
                return false;
        }
    }

    /**
     * Increment and check question count for the project.
     */
    public function shouldStopQuestions(Project $project, array $requirementsAnalysis = []): bool
    {
        $context = $project->ai_context ?? [];
        $asked   = (int) ($context['questions_asked_count'] ?? 0);

        if ($asked >= self::MAX_QUESTION_ROUNDS) {
            return true;
        }

        return (bool) ($requirementsAnalysis['stop_asking_questions'] ?? false)
            || (bool) ($requirementsAnalysis['is_complete'] ?? false)
            || ((int) ($requirementsAnalysis['completeness_score'] ?? 0) >= 80);
    }
}
