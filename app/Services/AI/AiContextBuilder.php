<?php

namespace App\Services\AI;

use App\Models\Project;
use Illuminate\Support\Facades\Log;

/**
 * AiContextBuilder
 *
 * Single responsibility: assemble the LLM message array (system prompt + history + current message)
 * following OpenAI's lean-prompt best practices:
 *
 *  1. Base prompt  — persona & core rules (static, ~150 tokens).
 *  2. Stage prompt — rules relevant to the CURRENT pipeline stage only.
 *  3. Memory block — only the memory fields required by the current stage.
 *  4. History      — last N conversation turns.
 *  5. User message — the current inbound message.
 */
class AiContextBuilder
{
    /**
     * Memory fields exposed per pipeline stage.
     * Only what the model actually needs for the current task.
     */
    private const STAGE_MEMORY_FIELDS = [
        'GREETING'  => [],                                                                       // No project context needed
        'DISCOVERY' => ['goal', 'pending_features'],
        'VALUATION' => ['goal', 'pending_features', 'tech_stack'],
        'PROPOSAL'  => ['goal', 'pending_features', 'invoice_status'],
        'EXECUTION' => ['goal', 'completed_features', 'pending_features', 'invoice_status'],
        'COMPLETED' => ['goal', 'completed_features', 'invoice_status'],
    ];

    /**
     * Base path for prompt template files.
     */
    private string $promptBasePath;

    public function __construct()
    {
        $this->promptBasePath = resource_path('prompts/ai_project_manager');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Public API
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Build the complete messages array ready to send to an LLM.
     *
     * @param  Project $project       The active project (used for ai_context memory).
     * @param  string  $cleanBody     Sanitized current user message.
     * @param  array   $history       Pre-formatted history: [['role'=>'user'|'assistant', 'content'=>'...'], ...]
     * @param  string  $format        'openai' (default) or 'gemini'
     * @return array                  Messages array ready for the LLM API call.
     */
    public function build(Project $project, string $cleanBody, array $history, string $format = 'openai'): array
    {
        $stage        = $this->resolveStage($project);
        $systemPrompt = $this->buildSystemPrompt($project, $stage);

        if ($format === 'gemini') {
            return $this->buildGeminiMessages($systemPrompt, $history, $cleanBody);
        }

        return $this->buildOpenAiMessages($systemPrompt, $history, $cleanBody);
    }

    /**
     * Resolve the current pipeline stage from project context, defaulting to GREETING.
     */
    public function resolveStage(Project $project): string
    {
        $context = $project->ai_context ?? [];
        $stage   = strtoupper(trim($context['current_stage'] ?? 'GREETING'));

        $validStages = ['GREETING', 'DISCOVERY', 'VALUATION', 'PROPOSAL', 'EXECUTION', 'COMPLETED'];
        return in_array($stage, $validStages, true) ? $stage : 'GREETING';
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private: System Prompt Assembly
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Assemble the final system prompt from layered parts.
     *
     * Order: Base Persona → Stage Instructions → Memory Block
     */
    private function buildSystemPrompt(Project $project, string $stage): string
    {
        $parts = [];

        // Layer 1: Base persona (always loaded)
        $base = $this->loadPromptFile('base');
        if (!empty($base)) {
            $parts[] = $base;
        }

        // Layer 2: Stage-specific instructions
        $stageFile = $this->loadPromptFile('stages/' . strtolower($stage));
        if (!empty($stageFile)) {
            $parts[] = $stageFile;
        }

        // Layer 3: Full Project Snapshot (Summary, Scope, Contracts, Payments, Support, Tasks & Company Policies)
        $snapshot = $this->buildProjectSnapshot($project);
        $snapshotJson = json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $parts[] = "## Full Project Snapshot\n```json\n{$snapshotJson}\n```";

        // Layer 4: Compact Memory Block
        $memoryBlock = $this->buildMemoryBlock($project, $stage);
        if (!empty($memoryBlock)) {
            $parts[] = $memoryBlock;
        }

        return implode("\n\n---\n\n", $parts);
    }

    /**
     * Build a compact memory block containing only the fields needed for the current stage.
     */
    private function buildMemoryBlock(Project $project, string $stage): string
    {
        $fields  = self::STAGE_MEMORY_FIELDS[$stage] ?? [];
        if (empty($fields)) {
            return '';
        }

        $context = $project->ai_context ?? [];
        $memory  = [];

        foreach ($fields as $field) {
            $value = $context[$field] ?? null;

            // Skip empty/default values to keep the prompt lean
            if ($value === null || $value === '' || $value === [] || $value === 'none') {
                continue;
            }

            $memory[$field] = $value;
        }

        if (empty($memory)) {
            return '';
        }

        $json = json_encode($memory, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        return "## Project Memory\n```json\n{$json}\n```";
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private: Message Array Builders
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Build OpenAI-format messages array.
     */
    private function buildOpenAiMessages(string $systemPrompt, array $history, string $cleanBody): array
    {
        $messages = [['role' => 'system', 'content' => $systemPrompt]];

        foreach ($history as $msg) {
            $messages[] = $msg;
        }

        $messages[] = ['role' => 'user', 'content' => $cleanBody];

        return $messages;
    }

    /**
     * Build Gemini-format contents array (alternating user/model turns).
     * System prompt injected as a primed user/model pair at the start.
     */
    private function buildGeminiMessages(string $systemPrompt, array $history, string $cleanBody): array
    {
        $contents = [
            ['role' => 'user',  'parts' => [['text' => $systemPrompt]]],
            ['role' => 'model', 'parts' => [['text' => 'فهمت التعليمات، أنا جاهز أساعد العميل.']]],
        ];

        foreach ($history as $msg) {
            // History items arrive with 'role' => 'user'|'assistant' and 'content' => '...'
            $geminiRole   = ($msg['role'] === 'assistant') ? 'model' : 'user';
            $contents[] = [
                'role'  => $geminiRole,
                'parts' => [['text' => $msg['content']]],
            ];
        }

        $contents[] = ['role' => 'user', 'parts' => [['text' => $cleanBody]]];

        return $contents;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private: File Loading
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Load a prompt template file relative to the prompt base path.
     * Returns empty string if the file does not exist (graceful degradation).
     */
    private function loadPromptFile(string $relativePath): string
    {
        $fullPath = $this->promptBasePath . DIRECTORY_SEPARATOR . $relativePath . '.md';

        if (!file_exists($fullPath)) {
            Log::warning("[AiContextBuilder] Prompt file not found: {$fullPath}");
            return '';
        }

        return trim(file_get_contents($fullPath));
    }

    /**
     * Build full Project Snapshot as required by the AI Project Manager workflow.
     */
    public function buildProjectSnapshot(Project $project): array
    {
        $context = $project->ai_context ?? [];
        $summary = $project->ai_summary ?? [];

        // Contract status
        $contract = null;
        try {
            $contract = $project->contracts()->latest()->first();
        } catch (\Throwable $e) {
            $contract = null;
        }

        $contractStatus = $contract ? [
            'status'       => $contract->status ?? 'draft',
            'signed_at'    => $contract->signed_at ?? null,
            'title'        => $contract->title ?? 'Main Contract',
            'has_contract' => true,
        ] : [
            'status'       => 'none',
            'has_contract' => false,
        ];

        // Financial & 50% Payment status
        $budget     = (float) ($project->budget ?? 0.0);
        $totalPaid  = (float) ($project->total_paid ?? 0.0);
        $is50PctPaid = $budget > 0 ? ($totalPaid >= ($budget * 0.50)) : ($totalPaid > 0);

        // Support Status (Default 30 days free support post project completion)
        $completedAt       = $project->archived_at ?? ($context['completed_at'] ?? null);
        $supportActive     = false;
        $supportExpiration = null;

        if ($completedAt) {
            $expDate           = \Carbon\Carbon::parse($completedAt)->addDays(30);
            $supportExpiration = $expDate->toIso8601String();
            $supportActive     = \Carbon\Carbon::now('Africa/Cairo')->lessThanOrEqualTo($expDate);
        }

        // Active Tasks & Todos
        $existingTasks = [];
        try {
            $existingTasks = \App\Models\ProjectBoardItem::where('project_id', $project->id)
                ->take(20)
                ->pluck('title')
                ->toArray();
        } catch (\Throwable $e) {
            $existingTasks = [];
        }

        $existingTodos = [];
        try {
            $existingTodos = \App\Models\Todo::where('project_id', $project->id)
                ->take(20)
                ->pluck('title')
                ->toArray();
        } catch (\Throwable $e) {
            $existingTodos = [];
        }

        // Recent Messages & Summary
        $recentMessages = [];
        $conversationSummary = $context['conversation_summary'] ?? 'جلسة حوارية جديدة لم تبدأ بعد.';

        return [
            'Project' => [
                'Summary'       => $summary['project_type'] ?? ($context['current_archetype'] ?? $project->project_name ?? 'مشروع جديد'),
                'Current Scope' => $context['approved_scope'] ?? [],
                'Requirements'  => [
                    'pending'   => $context['pending_features'] ?? [],
                    'completed' => $context['completed_features'] ?? [],
                ],
                'Context' => [
                    'stage'     => $this->resolveStage($project),
                    'goal'      => $context['current_goal'] ?? $project->description ?? 'بناء وتطوير البرمجيات',
                    'archetype' => $context['current_archetype'] ?? null,
                ],
                'AI Memory' => $context['ai_memory'] ?? [],
            ],
            'Commercial' => [
                'Estimate' => [
                    'budget_usd'          => $budget,
                    'recommended_usd'     => $context['recommended_usd'] ?? $budget,
                    'estimated_days'      => $context['estimated_days'] ?? 7,
                    'last_valuation'      => $context['last_valuation'] ?? null,
                ],
                'Contract' => $contractStatus,
                'Invoice' => [
                    'total_paid_usd'      => $totalPaid,
                    'balance_due_usd'     => max(0, $budget - $totalPaid),
                ],
                'Payment Status' => [
                    'is_50pct_paid'       => $is50PctPaid,
                    'is_fully_paid'       => $budget > 0 ? ($totalPaid >= $budget) : false,
                ],
            ],
            'Support' => [
                'Support Active'          => $supportActive,
                'Support End Date'        => $supportExpiration,
            ],
            'Execution' => [
                'Tasks'                   => $existingTasks,
                'Todos'                   => $existingTodos,
                'Notes'                   => $context['developer_notes'] ?? [],
            ],
            'Conversation' => [
                'Recent Messages'         => $recentMessages,
                'Conversation Summary'    => $conversationSummary,
            ],
            'Company Policies' => [
                'Pricing'                 => 'التسعير يعتمد على معايير السوق المحلية وتكلفة المكونات الدقيقة (Micro-Components).',
                'Support'                 => '30 يوماً دعم مجاني بعد التسليم النهائي لإصلاح أي عيوب برمجية (Bugs) داخل العقد.',
                'External Services'       => 'تكاليف الاستضافة، والدومينات، وبوابات الدفع والتطبيقات الخارجية يتحملها العميل بشكل مستقل.',
                'Source Code Ownership'   => 'الكود المصدري كاملاً ملك للعميل فور سداد مستحقات المشروع بالكامل.',
            ],
        ];
    }
}
