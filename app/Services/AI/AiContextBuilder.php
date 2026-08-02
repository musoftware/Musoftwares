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

        // Layer 3: Memory — only fields relevant to the current stage
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
}
