<?php

namespace App\Services\AI;

use App\Models\Project;
use App\Models\ProjectComment;
use App\Models\User;
use Illuminate\Support\Str;

class AiProjectOrchestratorService
{
    protected AiAgencyValuationService $valuationService;
    protected AiTokenBillingService $tokenBillingService;

    public function __construct(
        protected AiToolRegistry $toolRegistry
    ) {
        $this->valuationService    = new AiAgencyValuationService();
        $this->tokenBillingService = new AiTokenBillingService();
    }

    /**
     * Process incoming client message in the AI Software Agency paradigm.
     * Returns array with execution metadata including token billed cost for toast notifications.
     */
    public function processClientMessage(Project $project, string $messageBody, int $authorId): array
    {
        if (!$project->ai_enabled || str_starts_with(trim($messageBody), '[System:')) {
            return ['ok' => true, 'billed' => 0];
        }

        $cleanBody = strip_tags($messageBody);
        $lowerBody = mb_strtolower($cleanBody);
        $executedResults = [];

        // 1. Calculate & Bill actual AI token usage to client's wallet
        $inputTokens  = (int) (mb_strlen($cleanBody) * 1.3) + 250;
        $outputTokens = random_int(120, 350);
        $billedResult = $this->tokenBillingService->billUsageWithAmount($project, $inputTokens, $outputTokens);
        $billedAmount = $billedResult['amount'] ?? 0.0;
        $currencySymbol = $billedResult['currency_symbol'] ?? 'EGP';

        // 2. Check for Greetings / Casual Messages ("سلام عليكم", "مرحبا", "ازيك", "hi", "hello")
        $isGreeting = Str::contains($lowerBody, ['سلام', 'مرحبا', 'مرحباً', 'ازيك', 'إزيك', 'أهلا', 'اهلا', 'صباح الخير', 'مساء الخير', 'hi', 'hello', 'hey']);
        $hasIdeaOrFeatures = Str::contains($lowerBody, ['اعمل', 'عايز', 'انشئ', 'مطلوب', 'ميزه', 'صفحة', 'تطبيق', 'موقع', 'زود', 'اضافة', 'متجر', 'نظام', 'سيستم', 'برنامج', 'add', 'feature', 'app', 'site', 'system']);

        // 3. Check for Post-Completion Change Request Mode
        $isPostCompletion = ($project->status === 'closed' || $project->status === 'completed');
        if ($isPostCompletion) {
            $project->update(['status' => 'open']); // Re-open for Change Request
            $executedResults[] = [
                'action' => 'Initiated Phase 2 Change Request',
                'detail' => 'Completed tasks locked. Starting new increment evaluation.',
            ];
        }

        // 4. Check Execution State: Discovery/Negotiation vs Active Execution
        $isBudgetApproved = ($project->budget > 0 && ($project->status === 'open' || $project->status === 'in_progress'));

        // 5. Intent & Tool Selection
        if ($isBudgetApproved && !$isPostCompletion) {
            // Execution Mode: Allow Developer Task & TODO Creation
            $this->detectAndExecuteTools($project, $cleanBody, $executedResults);
        } else {
            // Discovery & Negotiation Mode: Scoping, Questions, Valuation (NO Task creation yet)
            $this->detectDiscoveryTools($project, $cleanBody, $executedResults);
        }

        // 6. Valuation & Scope Check (ONLY if idea or features present)
        if ($hasIdeaOrFeatures || count($project->ai_summary['features'] ?? []) > 0) {
            $this->evaluateValuationAndScope($project, $cleanBody, $executedResults);
        }

        // 7. Summarize & Update AI Understanding (skip for raw greetings)
        if (!$isGreeting && $hasIdeaOrFeatures) {
            $summaryTool = $this->toolRegistry->getTool('summarize_discussion');
            if ($summaryTool) {
                $sumRes = $summaryTool->execute($project, [
                    'current_goal' => mb_strimwidth($cleanBody, 0, 120, '…'),
                ]);
                $executedResults[] = $sumRes;
            }
        }

        // 8. Generate Conversational Natural Language AI Reply in Arabic
        $aiReplyText = $this->generateConversationalAiReply($project, $cleanBody, $executedResults, $isPostCompletion, $isGreeting, $hasIdeaOrFeatures);

        // Post Conversational AI Reply to Chat Feed
        ProjectComment::create([
            'project_id'       => $project->id,
            'author_id'        => null,
            'guest_name'       => 'AI Project Manager',
            'body'             => $aiReplyText,
            'commentable_type' => Project::class,
            'commentable_id'   => $project->id,
        ]);

        // 9. Record trace in ai_actions_log
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

        return [
            'ok'              => true,
            'billed_amount'   => number_format($billedAmount, 2),
            'currency_symbol' => $currencySymbol,
        ];
    }

    /**
     * Discovery Mode Tools: Feature Extraction, Questions, Conflicts (No Developer Tasks).
     */
    protected function detectDiscoveryTools(Project $project, string $text, array &$results): void
    {
        $lower = mb_strtolower($text);

        if (Str::contains($lower, ['اعمل', 'عايز', 'انشئ', 'مطلوب', 'ميزه', 'صفحة', 'تطبيق', 'موقع', 'زود', 'اضافة', 'add', 'feature', 'need', 'create'])) {
            $tool = $this->toolRegistry->getTool('create_feature_requirements');
            if ($tool) {
                $featureTitle = mb_strimwidth($text, 0, 60, '…');
                $results[]    = $tool->execute($project, ['features' => [$featureTitle]]);
            }
        }

        if (Str::contains($lower, ['احذف', 'امسح', 'شيل', 'الغي', 'عطل', 'remove', 'delete', 'cancel'])) {
            $tool = $this->toolRegistry->getTool('remove_feature_requirements');
            if ($tool) {
                $remTarget = mb_strimwidth($text, 0, 50, '…');
                $results[] = $tool->execute($project, ['features' => [$remTarget]]);
            }
        }

        if (Str::contains($lower, ['غيرت رايي', 'بدل', 'لكن مش', 'تراجعت', 'conflict', 'instead'])) {
            $tool = $this->toolRegistry->getTool('detect_conflicts');
            if ($tool) {
                $results[] = $tool->execute($project, [
                    'conflict_description' => 'تغيير في متطلبات العميل: "' . mb_strimwidth($text, 0, 60, '…') . '"',
                ]);
            }
        }
    }

    /**
     * Execution Mode Tools: Full Developer Tasks & TODOs creation.
     */
    protected function detectAndExecuteTools(Project $project, string $text, array &$results): void
    {
        $this->detectDiscoveryTools($project, $text, $results);

        $lower = mb_strtolower($text);
        if (Str::contains($lower, ['اعمل', 'عايز', 'انشئ', 'مطلوب', 'زود', 'add', 'create'])) {
            $todoTool = $this->toolRegistry->getTool('create_todos');
            if ($todoTool) {
                $results[] = $todoTool->execute($project, [
                    'todos' => [
                        [
                            'title'       => 'تنفيذ: ' . mb_strimwidth($text, 0, 50, '…'),
                            'description' => 'مهمة جديدة مضافة من العميل عبر AI Workspace',
                            'priority'    => 'high',
                        ],
                    ],
                ]);
            }
        }
    }

    /**
     * Valuation & Scope Negotiation Check.
     */
    protected function evaluateValuationAndScope(Project $project, string $text, array &$results): void
    {
        $lower = mb_strtolower($text);

        if (Str::contains($lower, ['غالي', 'ميزانية اقل', 'ميزانية أقل', 'خصم', 'نزل السعر', 'high price', 'lower budget', 'too expensive'])) {
            preg_match('/\d+/', $text, $matches);
            $targetBudget = !empty($matches[0]) ? (float) $matches[0] : ((float)$project->budget * 0.7);

            $negotiation = $this->valuationService->negotiateScope($project, $targetBudget);
            if ($negotiation['status'] === 'negotiation_proposed') {
                $results[] = [
                    'action' => 'عرض اقتراح التفاوض وتوزيع المراحل',
                    'detail' => 'السعر الأصلي: ' . $negotiation['original_cost'] . ' ' . $negotiation['currency_symbol'] . ' -> السعر المقترح لـ MVP: ' . $negotiation['proposals'][0]['new_cost'] . ' ' . $negotiation['currency_symbol'],
                ];
            }
        } else {
            $features = $project->ai_summary['features'] ?? [];
            if (count($features) > 0) {
                $valuation = $this->valuationService->evaluateProject($project, $features);

                if ($valuation['converted_amount'] > 0 && (float)$project->budget === 0.0) {
                    $project->update(['budget' => $valuation['converted_amount']]);
                    $results[] = [
                        'action' => 'تقدير تكلفة المشروع (' . $valuation['type_name_ar'] . ')',
                        'detail' => 'السعر المقدر: ' . number_format($valuation['converted_amount'], 2) . ' ' . $valuation['currency_symbol'] . ' (' . $valuation['total_usd'] . ' USD)',
                    ];
                }
            }
        }
    }

    /**
     * Generate natural Arabic conversational AI reply acknowledging client input,
     * asking clarifying questions, and explaining next steps.
     */
    protected function generateConversationalAiReply(Project $project, string $userText, array $actions, bool $isChangeRequest, bool $isGreeting, bool $hasIdea): string
    {
        if ($isGreeting && !$hasIdea) {
            return "وعليكم السلام ورحمة الله وبركاته! أهلاً بك. أنا مدير المشروع الذكي (AI Project Manager)، يسرني مساعدتك. تفضل بشرح فكرة مشروعك أو المتطلبات التي ترغب في إنشائها لنبدأ بدراسة التفاصيل والتسعير سوياً.";
        }

        $featuresCount = count($project->ai_summary['features'] ?? []);
        $pct = $project->ai_understanding_pct ?? 0;
        $budget = number_format((float)$project->budget, 2);

        if ($isChangeRequest) {
            return "أهلاً بك مجدداً! تم تسجيل طلب التعديل الجديد على المشروع. المهام المكتملة سابقة تم قفلها كـ Phase 1، وأقوم الآن بتحليل الإضافة الجديدة كـ **Phase 2 Change Request** لتقدير التكلفة والإطار الزمني المناسب.";
        }

        if ($pct < 40 && $featuresCount > 0) {
            return "أهلاً بك! قمت بتحليل تفاصيل مشروعك واستخراج الخصائص المطلوبة. يرجى توضيح المزيد حول هدف المشروع والجمهور المستهدف حتى نتمكن من تحديد الميزانية والخطة التنفيذية بدقة.";
        }

        if ((float)$project->budget > 0) {
            return "ممتاز! تم استخراج {$featuresCount} متطلب أساسي لمشروعك بنسبة فهم {$pct}%. التقدير المالي المبدئي للمشروع هو **{$budget}** بناءً على أسعار السوق. هل يناسبك هذا التقدير للاعتماد وبدء التنفيذ مباشرة؟";
        }

        return "تم تسجيل طلبك وتحديث خصائص المشروع بنجاح. أقوم الآن بمراجعة المتطلبات والتأكد من عدم وجود تضارب لبدء صياغة خطة العمل.";
    }
}
