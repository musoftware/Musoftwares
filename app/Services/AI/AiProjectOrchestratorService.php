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
    protected AiTokenBillingService $tokenBillingService;
    protected AiContextBuilder      $contextBuilder;

    /** Maximum number of sequential tool-call rounds before we force a text reply. */
    private const MAX_TOOL_ITERATIONS = 5;

    /** Maximum allowed raw message size (characters). */
    private const MAX_MESSAGE_CHARS = 4000;

    /** Rate limit: max AI messages per project per minute. */
    private const RATE_LIMIT_PER_MINUTE = 10;

    /** Admin-settings cache TTL in seconds. */
    private const SETTINGS_CACHE_TTL = 300;

    /** Conversation history window (number of past messages sent to the LLM). */
    private const HISTORY_WINDOW = 10;

    public function __construct(
        protected AiToolRegistry $toolRegistry
    ) {
        $this->tokenBillingService = new AiTokenBillingService();
        $this->contextBuilder      = new AiContextBuilder();
    }

    /**
     * Process client message using the Lean Prompt / Layered Context Architecture.
     *
     * Flow:
     *  1. Guards (system message, rate limit).
     *  2. Sanitise input.
     *  3. Load cached settings.
     *  4. Resolve pipeline stage → AiContextBuilder builds the lean system prompt.
     *  5. Load history window (last N messages).
     *  6. OpenAI provider with multi-turn tool loop.
     *  7. Gemini fallback with multi-turn tool loop.
     *  8. DB::transaction — billing + persist reply.
     *  9. Structured log.
     */
    public function processClientMessage(Project $project, string $messageBody, int $authorId): array
    {
        // ── Guard: ignore system-generated messages ──────────────────────────────
        if (str_starts_with(trim($messageBody), '[System:')) {
            return ['ok' => true, 'billed' => 0];
        }

        // ── Guard: rate limit per project (10 req / min) ─────────────────────────
        $rateLimitKey = 'ai_project:' . $project->id;
        if (RateLimiter::tooManyAttempts($rateLimitKey, self::RATE_LIMIT_PER_MINUTE)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            Log::warning("[AI Orchestrator] Rate limit for project #{$project->id}. Retry in {$seconds}s.");
            return [
                'ok'    => false,
                'error' => "تم إرسال رسائل كثيرة. يرجى الانتظار {$seconds} ثانية ثم المحاولة مجدداً.",
            ];
        }
        RateLimiter::hit($rateLimitKey, 60);

        if (!$project->ai_enabled) {
            $project->update(['ai_enabled' => true]);
        }

        // ── 1. Sanitise & size-limit incoming message ────────────────────────────
        $cleanBody = $this->sanitizeInput($messageBody);
        if (empty($cleanBody)) {
            return ['ok' => false, 'error' => 'الرسالة فارغة بعد التنظيف.'];
        }

        // ── 2. Load API keys & models from cached admin settings ─────────────────
        $adminSettings = Cache::remember('ai_admin_settings', self::SETTINGS_CACHE_TTL, function () {
            return AdminSettings::pluck('setting_value', 'setting_key')->toArray();
        });

        $openAiKey   = !empty($adminSettings['openai_api_key'])  ? $adminSettings['openai_api_key']  : config('services.openai.key');
        $openAiModel = !empty($adminSettings['openai_model'])     ? $adminSettings['openai_model']     : 'gpt-4o-mini';
        $geminiKey   = !empty($adminSettings['gemini_api_keys'])  ? $adminSettings['gemini_api_keys']  : config('services.gemini.key');
        $geminiModel = !empty($adminSettings['gemini_model'])     ? $adminSettings['gemini_model']     : 'gemini-2.0-flash';

        // ── 3. Resolve pipeline stage ────────────────────────────────────────────
        $stage = $this->contextBuilder->resolveStage($project);

        // ── 4. Load conversation history (lean window: last N messages) ──────────
        $recentDiscussions = ProjectComment::where('project_id', $project->id)
            ->latest()
            ->take(self::HISTORY_WINDOW)
            ->get()
            ->reverse()
            ->values();

        // Convert to provider-neutral format: [['role' => 'user'|'assistant', 'content' => '...']]
        $history = $recentDiscussions->map(fn ($comm) => [
            'role'    => $comm->author_id ? 'user' : 'assistant',
            'content' => $this->sanitizeInput($comm->body),
        ])->all();

        // ── 5. Build stage-filtered tool list ────────────────────────────────────
        $stageTools = $this->toolRegistry->toolsForStage($stage);

        // ── 6. Initialise counters ───────────────────────────────────────────────
        $totalPromptTokens     = 0;
        $totalCompletionTokens = 0;
        $activeModelUsed       = $openAiModel;
        $aiReplyText           = '';
        $executedTools         = [];
        $startTime             = microtime(true);

        // ── 7. OpenAI Provider ───────────────────────────────────────────────────
        if (!empty($openAiKey)) {
            try {
                // AiContextBuilder: base.md + stage/{stage}.md + lean memory + history + current message
                $openAiMessages = $this->contextBuilder->build($project, $cleanBody, $history, 'openai');
                $openAiTools    = $this->formatOpenAiTools($stageTools);

                $payload = [
                    'model'       => $openAiModel,
                    'messages'    => $openAiMessages,
                    'temperature' => 0.5,
                ];
                if (!empty($openAiTools)) {
                    $payload['tools']       = $openAiTools;
                    $payload['tool_choice'] = 'auto';
                }

                $response = Http::withoutVerifying()
                    ->retry(2, 600, fn (\Throwable $e, $response) => $this->isRetryable($response))
                    ->timeout(40)
                    ->withToken($openAiKey)
                    ->post('https://api.openai.com/v1/chat/completions', $payload);

                if ($response->successful()) {
                    $activeModelUsed = $openAiModel;
                    $this->accumulateUsage($response->json('usage') ?? [], $totalPromptTokens, $totalCompletionTokens);

                    $choice     = $response->json('choices.0.message');
                    $iterations = 0;

                    // Multi-turn tool-call loop — supports Tool → Tool → … → Reply chains
                    while (
                        !empty($choice['tool_calls']) &&
                        is_array($choice['tool_calls']) &&
                        $iterations < self::MAX_TOOL_ITERATIONS
                    ) {
                        $iterations++;
                        $openAiMessages[] = $choice; // append assistant's tool-call turn

                        foreach ($choice['tool_calls'] as $toolCall) {
                            $fnName = $toolCall['function']['name'] ?? '';
                            $fnArgs = json_decode($toolCall['function']['arguments'] ?? '{}', true) ?: [];

                            Log::info('[AI Orchestrator] OpenAI tool call', [
                                'tool'       => $fnName,
                                'project_id' => $project->id,
                                'stage'      => $stage,
                                'iteration'  => $iterations,
                                'args'       => $fnArgs,
                            ]);

                            $tool = $this->toolRegistry->getTool($fnName);
                            try {
                                $res = $tool ? $tool->execute($project, $fnArgs) : ['error' => "Unknown tool: {$fnName}"];
                            } catch (\Throwable $te) {
                                Log::error("[AI Orchestrator] Tool '{$fnName}' execution exception", [
                                    'error'      => $te->getMessage(),
                                    'project_id' => $project->id,
                                ]);
                                $res = ['error' => "Tool execution error: " . $te->getMessage()];
                            }
                            $executedTools[] = ['tool' => $fnName, 'result' => $res];

                            $openAiMessages[] = [
                                'role'         => 'tool',
                                'tool_call_id' => $toolCall['id'] ?? '',
                                'content'      => json_encode($res),
                            ];
                        }

                        // Re-call OpenAI with tool results
                        $followUp = Http::withoutVerifying()
                            ->retry(2, 600, fn (\Throwable $e, $response) => $this->isRetryable($response))
                            ->timeout(40)
                            ->withToken($openAiKey)
                            ->post('https://api.openai.com/v1/chat/completions', [
                                'model'       => $openAiModel,
                                'messages'    => $openAiMessages,
                                'tools'       => $openAiTools,
                                'tool_choice' => 'auto',
                                'temperature' => 0.5,
                            ]);

                        if (!$followUp->successful()) {
                            Log::error('[AI Orchestrator] OpenAI tool follow-up failed', [
                                'status'     => $followUp->status(),
                                'project_id' => $project->id,
                            ]);
                            break;
                        }

                        $this->accumulateUsage($followUp->json('usage') ?? [], $totalPromptTokens, $totalCompletionTokens);
                        $choice = $followUp->json('choices.0.message');
                    }

                    $aiReplyText = $choice['content'] ?? '';
                } else {
                    Log::error('[AI Orchestrator] OpenAI API failed', [
                        'status'     => $response->status(),
                        'body'       => $response->body(),
                        'project_id' => $project->id,
                    ]);
                }
            } catch (\Throwable $e) {
                Log::error('[AI Orchestrator] OpenAI exception', [
                    'message'    => $e->getMessage(),
                    'project_id' => $project->id,
                ]);
            }
        }

        // ── 8. Gemini Fallback Provider ──────────────────────────────────────────
        if (empty($aiReplyText) && !empty($geminiKey)) {
            try {
                // AiContextBuilder handles Gemini's alternating user/model format
                $geminiContents = $this->contextBuilder->build($project, $cleanBody, $history, 'gemini');

                $geminiToolDeclarations = [];
                foreach ($stageTools as $tool) {
                    $geminiToolDeclarations[] = [
                        'name'        => $tool->name(),
                        'description' => $tool->description(),
                        'parameters'  => $tool->parameters(),
                    ];
                }

                $geminiPayload = [
                    'contents'         => $geminiContents,
                    'generationConfig' => ['temperature' => 0.5],
                ];
                if (!empty($geminiToolDeclarations)) {
                    $geminiPayload['tools'] = [['function_declarations' => $geminiToolDeclarations]];
                }

                $response = Http::withoutVerifying()
                    ->retry(2, 600, fn (\Throwable $e, $response) => $this->isRetryable($response))
                    ->timeout(35)
                    ->post("https://generativelanguage.googleapis.com/v1beta/models/{$geminiModel}:generateContent?key={$geminiKey}", $geminiPayload);

                if ($response->successful()) {
                    $activeModelUsed = $geminiModel;
                    $this->accumulateGeminiUsage($response->json('usageMetadata') ?? [], $totalPromptTokens, $totalCompletionTokens);

                    $parts      = $response->json('candidates.0.content.parts') ?? [];
                    $iterations = 0;

                    // Multi-turn Gemini tool-call loop
                    while ($iterations < self::MAX_TOOL_ITERATIONS) {
                        $iterations++;
                        $hasFunctionCall = false;

                        foreach ($parts as $part) {
                            if (empty($part['functionCall'])) {
                                continue;
                            }
                            $hasFunctionCall = true;

                            $fnName = $part['functionCall']['name'] ?? '';
                            $fnArgs = $part['functionCall']['args'] ?? [];

                            Log::info('[AI Orchestrator] Gemini tool call', [
                                'tool'       => $fnName,
                                'project_id' => $project->id,
                                'stage'      => $stage,
                                'iteration'  => $iterations,
                                'args'       => $fnArgs,
                            ]);

                            $tool = $this->toolRegistry->getTool($fnName);
                            try {
                                $res = $tool ? $tool->execute($project, $fnArgs) : ['error' => "Unknown tool: {$fnName}"];
                            } catch (\Throwable $te) {
                                Log::error("[AI Orchestrator] Tool '{$fnName}' execution exception", [
                                    'error'      => $te->getMessage(),
                                    'project_id' => $project->id,
                                ]);
                                $res = ['error' => "Tool execution error: " . $te->getMessage()];
                            }
                            $executedTools[] = ['tool' => $fnName, 'result' => $res];

                            $geminiContents[] = ['role' => 'model', 'parts' => $parts];
                            $geminiContents[] = [
                                'role'  => 'user',
                                'parts' => [['functionResponse' => ['name' => $fnName, 'response' => $res]]],
                            ];
                        }

                        if (!$hasFunctionCall) {
                            $aiReplyText = $parts[0]['text'] ?? '';
                            break;
                        }

                        $followUp = Http::withoutVerifying()
                            ->retry(2, 600, fn (\Throwable $e, $response) => $this->isRetryable($response))
                            ->timeout(35)
                            ->post("https://generativelanguage.googleapis.com/v1beta/models/{$geminiModel}:generateContent?key={$geminiKey}", [
                                'contents'         => $geminiContents,
                                'generationConfig' => ['temperature' => 0.5],
                            ]);

                        if (!$followUp->successful()) {
                            Log::error('[AI Orchestrator] Gemini tool follow-up failed', [
                                'status'     => $followUp->status(),
                                'project_id' => $project->id,
                            ]);
                            break;
                        }

                        $this->accumulateGeminiUsage($followUp->json('usageMetadata') ?? [], $totalPromptTokens, $totalCompletionTokens);
                        $parts = $followUp->json('candidates.0.content.parts') ?? [];
                    }
                } else {
                    Log::error('[AI Orchestrator] Gemini API failed', [
                        'status'     => $response->status(),
                        'body'       => $response->body(),
                        'project_id' => $project->id,
                    ]);
                }
            } catch (\Throwable $e) {
                Log::error('[AI Orchestrator] Gemini exception', [
                    'message'    => $e->getMessage(),
                    'project_id' => $project->id,
                ]);
            }
        }

        // ── 9. Connection Error Fallback ─────────────────────────────────────────
        if (empty($aiReplyText)) {
            $aiReplyText = 'عذراً، حدث انقطاع مؤقت في الاتصال بخدمة الذكاء الاصطناعي. يرجى إعادة إرسال رسالتك مرة أخرى.';
        }

        // ── 10. Billing + Persistence inside one DB Transaction ─────────────────
        $billedAmount   = 0.0;
        $currencySymbol = 'EGP';

        DB::transaction(function () use (
            $project, $aiReplyText, $totalPromptTokens, $totalCompletionTokens,
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

            ProjectComment::create([
                'project_id'       => $project->id,
                'author_id'        => null,
                'guest_name'       => 'AI Project Manager',
                'body'             => $aiReplyText,
                'commentable_type' => Project::class,
                'commentable_id'   => $project->id,
            ]);
        });

        // ── 11. Structured log ───────────────────────────────────────────────────
        $durationMs = round((microtime(true) - $startTime) * 1000);
        Log::info('[AI Orchestrator] Completed', [
            'project_id'        => $project->id,
            'stage'             => $stage,
            'model'             => $activeModelUsed,
            'prompt_tokens'     => $totalPromptTokens,
            'completion_tokens' => $totalCompletionTokens,
            'tools_available'   => count($stageTools),
            'executed_tools'    => array_column($executedTools, 'tool'),
            'billed_amount'     => $billedAmount,
            'currency'          => $currencySymbol,
            'duration_ms'       => $durationMs,
            'reply_length'      => mb_strlen($aiReplyText),
        ]);

        return [
            'ok'              => true,
            'billed_amount'   => number_format($billedAmount, 4),
            'currency_symbol' => $currencySymbol,
            'executed_tools'  => $executedTools,
        ];
    }

    // ════════════════════════════════════════════════════════════════════════════
    // Private helpers
    // ════════════════════════════════════════════════════════════════════════════

    /**
     * Sanitize and size-limit a message string.
     * Removes HTML tags, null bytes, control characters, and trims to MAX_MESSAGE_CHARS.
     */
    private function sanitizeInput(string $raw): string
    {
        $text = strip_tags($raw);
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text);
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        return mb_substr(trim($text), 0, self::MAX_MESSAGE_CHARS);
    }

    /**
     * Accumulate OpenAI usage tokens.
     */
    private function accumulateUsage(array $usage, int &$prompt, int &$completion): void
    {
        $prompt     += (int) ($usage['prompt_tokens']     ?? 0);
        $completion += (int) ($usage['completion_tokens'] ?? 0);
    }

    /**
     * Accumulate Gemini usage tokens.
     */
    private function accumulateGeminiUsage(array $meta, int &$prompt, int &$completion): void
    {
        $prompt     += (int) ($meta['promptTokenCount']     ?? 0);
        $completion += (int) ($meta['candidatesTokenCount'] ?? 0);
    }

    /**
     * Determine whether an HTTP response/exception warrants a retry.
     * Retries on: 429, 500, 502, 503, 504, and any connection-level exception.
     */
    private function isRetryable(mixed $response): bool
    {
        if ($response instanceof \Throwable) {
            return true;
        }
        return in_array($response?->status(), [429, 500, 502, 503, 504], true);
    }

    /**
     * Format a tool collection into the OpenAI function-declaration schema.
     *
     * @param  array<string, \App\Services\AI\Tools\AiToolInterface> $tools
     */
    private function formatOpenAiTools(array $tools): array
    {
        $openAiTools = [];
        foreach ($tools as $tool) {
            $openAiTools[] = [
                'type'     => 'function',
                'function' => [
                    'name'        => $tool->name(),
                    'description' => $tool->description(),
                    'parameters'  => $tool->parameters(),
                ],
            ];
        }
        return $openAiTools;
    }
}
