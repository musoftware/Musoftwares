<?php

namespace App\Services\AI;

use App\Models\AdminSettings;
use App\Models\Project;
use App\Models\ProjectComment;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class AiProjectOrchestratorService
{
    protected AiTokenBillingService     $tokenBillingService;
    protected AiContextBuilder          $contextBuilder;
    protected ConversationStateMachine  $stateMachine;
    protected AiContextManager          $contextManager;
    protected ScopePricingEngine        $pricingEngine;
    protected AiResponseValidator       $responseValidator;
    protected AiAgencyStructuredEngine  $structuredEngine;
    protected AiAgencyLaravelExecutor   $laravelExecutor;

    private const MAX_TOOL_ITERATIONS   = 5;
    private const MAX_MESSAGE_CHARS     = 4000;
    private const RATE_LIMIT_PER_MINUTE = 10;
    private const SETTINGS_CACHE_TTL    = 300;
    private const HISTORY_WINDOW        = 10;

    public function __construct(
        protected AiToolRegistry $toolRegistry
    ) {
        $this->tokenBillingService  = new AiTokenBillingService();
        $this->contextBuilder       = new AiContextBuilder();
        $this->stateMachine          = new ConversationStateMachine();
        $this->contextManager       = new AiContextManager();
        $this->pricingEngine        = new ScopePricingEngine();
        $this->responseValidator    = new AiResponseValidator();
        $this->structuredEngine     = new AiAgencyStructuredEngine();
        $this->laravelExecutor      = new AiAgencyLaravelExecutor();
    }

    /**
     * Single-Brain Orchestrator Flow:
     * User Message -> AI Engine Think (Single Brain) -> Pricing Calculator -> Validation & Context Update -> DB Persistence.
     */
    public function processClientMessage(Project $project, string $messageBody, int $authorId): array
    {
        // Guard: System messages
        if (str_starts_with(trim($messageBody), '[System:')) {
            return ['ok' => true, 'billed' => 0];
        }

        // Guard: Rate limiter
        $rateLimitKey = 'ai_project:' . $project->id;
        if (RateLimiter::tooManyAttempts($rateLimitKey, self::RATE_LIMIT_PER_MINUTE)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            Log::warning("[AI Orchestrator] Rate limit hit for project #{$project->id}. Retry in {$seconds}s.");
            return [
                'ok'    => false,
                'error' => "تم إرسال رسائل كثيرة. يرجى الانتظار {$seconds} ثانية ثم المحاولة مجدداً.",
            ];
        }
        RateLimiter::hit($rateLimitKey, 60);

        if (!$project->ai_enabled) {
            $project->update(['ai_enabled' => true]);
        }

        // 1. Sanitise input & initialize memory
        $cleanBody = $this->sanitizeInput($messageBody);
        if (empty($cleanBody)) {
            return ['ok' => false, 'error' => 'الرسالة فارغة بعد التنظيف.'];
        }

        $this->contextManager->ensureStructuredContext($project);
        $stage = $this->stateMachine->getCurrentStage($project);

        // 2. Load discussion history
        $recentDiscussions = ProjectComment::where('project_id', $project->id)
            ->latest()
            ->take(self::HISTORY_WINDOW)
            ->get()
            ->reverse()
            ->values();

        $history = $recentDiscussions->map(fn ($comm) => [
            'role'    => $comm->author_id ? 'user' : 'assistant',
            'content' => $this->sanitizeInput($comm->body),
        ])->all();

        // 3. SINGLE BRAIN THINKING: Call LLM to understand intent, detect conflicts & decide archetype
        $decision = $this->structuredEngine->think($project, $cleanBody, $history);

        // 4. CALCULATOR: Use AI's decided project_type & features to compute pricing valuation
        $decidedArchetype = $decision['project_type'] ?? 'corporate_website';
        $decidedFeatures  = $decision['context_updates']['pending_features'] ?? [];
        $valuation        = $this->pricingEngine->calculateValuation($project, $decidedFeatures, $decidedArchetype);

        // Attach valuation calculation to context updates
        $decision['context_updates']['current_archetype'] = $decidedArchetype;
        $decision['context_updates']['valuation']         = $valuation;
        $decision['context_updates']['conflict_detected'] = $decision['conflict_detected'] ?? false;
        $decision['context_updates']['reconciliation_reason'] = $decision['reconciliation_reason'] ?? '';

        // 5. State Machine Guard Validation
        $proposedStage = $decision['action_proposals']['stage_transition']
            ?? $decision['context_updates']['current_stage']
            ?? $stage;

        if ($this->stateMachine->canTransition($project, $proposedStage, $decision['requirements_analysis'] ?? [])) {
            $this->contextManager->updateStage($project, $proposedStage);
        }

        // 6. DB Billing & Execution
        $totalPromptTokens     = 120;
        $totalCompletionTokens = 180;
        $billedAmount          = 0.0;
        $currencySymbol        = 'EGP';
        $activeModelUsed       = 'gemini-2.0-flash';
        $startTime             = microtime(true);

        DB::transaction(function () use (
            $project, $decision, $authorId, $totalPromptTokens, $totalCompletionTokens,
            $activeModelUsed, &$billedAmount, &$currencySymbol
        ) {
            if ($totalPromptTokens > 0 || $totalCompletionTokens > 0) {
                $billedResult   = $this->tokenBillingService->billUsageWithAmount(
                    $project,
                    $totalPromptTokens,
                    $totalCompletionTokens,
                    $activeModelUsed,
                    'AI Project Manager Interaction'
                );
                $billedAmount   = (float) ($billedResult['amount'] ?? 0.0);
                $currencySymbol = $billedResult['currency_symbol'] ?? 'EGP';
            }

            // Persist context updates & AI reply
            $this->laravelExecutor->execute($project, $decision, $authorId);
        });

        // 7. Context Summarization if history > 10 messages
        $totalCommentCount = ProjectComment::where('project_id', $project->id)->count();
        $this->contextManager->summarizeIfNeeded($project, $totalCommentCount);

        $durationMs = round((microtime(true) - $startTime) * 1000);
        Log::info('[AI Orchestrator] Single Brain Execution Completed', [
            'project_id'        => $project->id,
            'archetype'         => $decidedArchetype,
            'conflict_detected' => $decision['conflict_detected'] ?? false,
            'intent'            => $decision['intent']['primary'] ?? 'unknown',
            'billed_amount'     => $billedAmount,
            'duration_ms'       => $durationMs,
        ]);

        return [
            'ok'              => true,
            'billed_amount'   => number_format($billedAmount, 4),
            'currency_symbol' => $currencySymbol,
            'decision'        => $decision,
        ];
    }

    /**
     * Sanitize and size-limit a message string.
     */
    private function sanitizeInput(string $raw): string
    {
        $text = strip_tags($raw);
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text);
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        return mb_substr(trim($text), 0, self::MAX_MESSAGE_CHARS);
    }
}
