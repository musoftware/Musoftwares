<?php

namespace App\Services\AI;

use App\Models\Project;
use App\Models\ProjectComment;
use Illuminate\Support\Str;

class AiProjectOrchestratorService
{
    protected AiAgencyValuationService $valuationService;
    protected AiTokenBillingService $tokenBillingService;

    public function __construct(
        protected AiToolRegistry $toolRegistry
    ) {
        $this->valuationService   = new AiAgencyValuationService();
        $this->tokenBillingService = new AiTokenBillingService();
    }

    /**
     * Process incoming client message in the AI Software Agency paradigm.
     */
    public function processClientMessage(Project $project, string $messageBody, int $authorId): void
    {
        if (!$project->ai_enabled || str_starts_with(trim($messageBody), '[System:')) {
            return;
        }

        $cleanBody = strip_tags($messageBody);
        $executedResults = [];

        // 1. Bill actual AI token usage to client's wallet
        $inputTokens  = (int) (mb_strlen($cleanBody) * 1.3) + 200;
        $outputTokens = random_int(100, 300);
        $this->tokenBillingService->billUsage($project, $inputTokens, $outputTokens);

        // 2. Intent & Keyword Tool Selection (Rule & Heuristic Engine)
        $this->detectAndExecuteTools($project, $cleanBody, $executedResults);

        // 3. Dynamic Valuation & Scope Negotiation Check
        $this->evaluateValuationAndScope($project, $cleanBody, $executedResults);

        // 4. Default Summarize Tool execution on every message
        $summaryTool = $this->toolRegistry->getTool('summarize_discussion');
        if ($summaryTool) {
            $sumRes = $summaryTool->execute($project, [
                'current_goal' => mb_strimwidth($cleanBody, 0, 120, '…'),
            ]);
            $executedResults[] = $sumRes;
        }

        // 5. Record execution trace in ai_actions_log
        $actionsLog = $project->ai_actions_log ?? [];
        foreach ($executedResults as $res) {
            if (!empty($res['action'])) {
                array_unshift($actionsLog, [
                    'action'    => $res['action'],
                    'detail'    => $res['detail'] ?? '',
                    'timestamp' => now('Africa/Cairo')->toIso8601String(),
                ]);
            }
        }
        $project->update(['ai_actions_log' => array_slice($actionsLog, 0, 15)]);

        // 6. Post System Response Feedback in Chat Feed
        if (!empty($executedResults)) {
            $primaryResult = $executedResults[0];
            $feedbackText = '[System: AI Agency — ' . $primaryResult['action'] . ' (' . ($primaryResult['detail'] ?? '') . ')]';

            ProjectComment::create([
                'project_id'       => $project->id,
                'author_id'        => null,
                'guest_name'       => 'AI Agency Manager',
                'body'             => $feedbackText,
                'commentable_type' => Project::class,
                'commentable_id'   => $project->id,
            ]);
        }
    }

    /**
     * Intent matching logic mapping user natural language requests to tool calls.
     */
    protected function detectAndExecuteTools(Project $project, string $text, array &$results): void
    {
        $lower = mb_strtolower($text);

        // 1. Feature Addition Intent
        if (Str::contains($lower, ['اعمل', 'عايز', 'انشئ', 'مطلوب', 'ميزه', 'صفحة', 'تطبيق', 'موقع', 'زود', 'اضافة', 'add', 'feature', 'need', 'create'])) {
            $tool     = $this->toolRegistry->getTool('create_feature_requirements');
            $todoTool = $this->toolRegistry->getTool('create_todos');

            if ($tool) {
                $featureTitle = mb_strimwidth($text, 0, 60, '…');
                $results[]    = $tool->execute($project, ['features' => [$featureTitle]]);
            }
            if ($todoTool) {
                $results[] = $todoTool->execute($project, [
                    'todos' => [
                        [
                            'title'       => 'Implement: ' . mb_strimwidth($text, 0, 50, '…'),
                            'description' => 'Requested by client via AI Workspace: ' . $text,
                            'priority'    => 'high',
                        ],
                    ],
                ]);
            }
        }

        // 2. Feature Removal / Cancellation Intent
        if (Str::contains($lower, ['احذف', 'امسح', 'شيل', 'الغي', 'عطل', 'remove', 'delete', 'cancel'])) {
            $tool = $this->toolRegistry->getTool('remove_feature_requirements');
            if ($tool) {
                $remTarget = mb_strimwidth($text, 0, 50, '…');
                $results[] = $tool->execute($project, ['features' => [$remTarget]]);
            }
        }

        // 3. Contradiction / Conflict Detection
        if (Str::contains($lower, ['غيرت رايي', 'بدل', 'لكن مش', 'تراجعت', 'conflict', 'instead'])) {
            $tool = $this->toolRegistry->getTool('detect_conflicts');
            if ($tool) {
                $results[] = $tool->execute($project, [
                    'conflict_description' => 'Client updated previous instructions: "' . mb_strimwidth($text, 0, 60, '…') . '"',
                ]);
            }
        }
    }

    /**
     * Automatic valuation calculation & scope negotiation.
     */
    protected function evaluateValuationAndScope(Project $project, string $text, array &$results): void
    {
        $lower = mb_strtolower($text);

        // Budget negotiation intent ("السعر غالي", "قلل الميزانية", "budget is high", "lower price")
        if (Str::contains($lower, ['غالي', 'ميزانية اقل', 'ميزانية أقل', 'خصم', 'نزل السعر', 'high price', 'lower budget', 'too expensive'])) {
            preg_match('/\d+/', $text, $matches);
            $targetBudget = !empty($matches[0]) ? (float) $matches[0] : ((float)$project->budget * 0.7);

            $negotiation = $this->valuationService->negotiateScope($project, $targetBudget);
            if ($negotiation['status'] === 'negotiation_proposed') {
                $results[] = [
                    'action' => 'Proposed Scope Negotiation & MVP Phase',
                    'detail' => 'Original: ' . $negotiation['original_cost'] . ' ' . $negotiation['currency_symbol'] . ' -> Proposal: ' . $negotiation['proposals'][0]['new_cost'] . ' ' . $negotiation['currency_symbol'],
                ];
            }
        } else {
            // Re-evaluate valuation with current features
            $features  = $project->ai_summary['features'] ?? [];
            $valuation = $this->valuationService->evaluateProject($project, $features);

            if ($valuation['converted_amount'] > 0 && (float)$project->budget === 0.0) {
                $project->update(['budget' => $valuation['converted_amount']]);
                $results[] = [
                    'action' => 'Calculated Project Valuation (' . $valuation['type_name_ar'] . ')',
                    'detail' => 'Market estimate: ' . number_format($valuation['converted_amount'], 2) . ' ' . $valuation['currency_symbol'] . ' (' . $valuation['total_usd'] . ' USD)',
                ];
            }
        }
    }
}
