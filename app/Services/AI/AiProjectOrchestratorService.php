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

    /** Maximum number of sequential tool-call rounds before we force a text reply. */
    private const MAX_TOOL_ITERATIONS = 5;

    /** Maximum allowed raw message size (characters). */
    private const MAX_MESSAGE_CHARS = 4000;

    /** Rate limit: max AI messages per project per minute. */
    private const RATE_LIMIT_PER_MINUTE = 10;

    /** Admin-settings cache TTL in seconds. */
    private const SETTINGS_CACHE_TTL = 300;

    public function __construct(
        protected AiToolRegistry $toolRegistry
    ) {
        $this->tokenBillingService = new AiTokenBillingService();
    }

    /**
     * Process client message in the Memory-Driven Conversation Engine Architecture.
     *
     * Principles:
     * 1. System Prompt = Persona + Project Memory + Conversation Memory.
     * 2. Project Memory = Permanent facts (goal, completed_features, pending_features, tech_stack, invoice_status).
     * 3. Conversation Memory = Living facts (conversation_summary, waiting_for).
     * 4. History Window = Last 15 messages (enough context without hitting token limits).
     * 5. Tool Calling = OpenAI natively triggers tools; supports multi-turn tool chains.
     * 6. Zero Laravel String Rules (Laravel is purely stateless execution & memory persistence).
     */
    public function processClientMessage(Project $project, string $messageBody, int $authorId): array
    {
        // ── Guard: ignore system-generated messages ─────────────────────────────
        if (str_starts_with(trim($messageBody), '[System:')) {
            return ['ok' => true, 'billed' => 0];
        }

        // ── Guard: rate limit per project (10 req/min) ──────────────────────────
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

        // ── 1. Sanitize & size-limit incoming message ────────────────────────────
        $cleanBody = $this->sanitizeInput($messageBody);

        if (empty($cleanBody)) {
            return ['ok' => false, 'error' => 'الرسالة فارغة بعد التنظيف.'];
        }

        // ── 2. Load API keys & models from cached admin settings ─────────────────
        $adminSettings = Cache::remember('ai_admin_settings', self::SETTINGS_CACHE_TTL, function () {
            return AdminSettings::pluck('setting_value', 'setting_key')->toArray();
        });

        $openAiKey   = !empty($adminSettings['openai_api_key'])   ? $adminSettings['openai_api_key']   : config('services.openai.key');
        $openAiModel = !empty($adminSettings['openai_model'])      ? $adminSettings['openai_model']      : 'gpt-4o-mini';
        $geminiKey   = !empty($adminSettings['gemini_api_keys'])   ? $adminSettings['gemini_api_keys']   : config('services.gemini.key');
        $geminiModel = !empty($adminSettings['gemini_model'])      ? $adminSettings['gemini_model']      : 'gemini-2.0-flash';

        // ── 3. Extract Project Memory & Conversation Memory ──────────────────────
        $context = $project->ai_context ?? [];

        $projectMemory = [
            'current_stage'      => $context['current_stage']          ?? 'GREETING',
            'goal'               => $context['goal']                   ?? $project->project_name,
            'completed_features' => $context['completed_features']     ?? [],
            'pending_features'   => $context['pending_features']       ?? [],
            'tech_stack'         => $context['tech_stack']             ?? 'Laravel, React, Inertia',
            'invoice_status'     => $context['current_invoice_status'] ?? 'none',
        ];

        $conversationMemory = [
            'summary'     => $context['conversation_summary'] ?? 'Conversation initiated.',
            'waiting_for' => $context['waiting_for']          ?? [],
        ];

        $projectMemoryJson      = json_encode($projectMemory, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $conversationMemoryJson = json_encode($conversationMemory, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        // ── 4. Build System Prompt ───────────────────────────────────────────────
        $systemPrompt = <<<PROMPT
أنت مبرمج مصري سينيور وقائد تقني (Senior Egyptian Software Developer & Tech Lead) في شركة برمجيات احترافية.

قواعد الشخصية وأسلوب الحوار:
1. تحدث بالعامية المصرية الدارجة السلسة والمباشرة الخاصة بالمبرمجين (مثال: "تمام يا هندسة"، "أمرك يا باشا"، "فل زي الفل"، "ظبطنا الـ logic"، "الـ API جاهز"، "كله تمام").
2. تجنب تماماً الأسلوب الفصيح الجاف أو الروبوتي، وتجنب تماماً الجمل الكليشيه المكررة (مثل "أنا بخير، كيف تسير الأمور مع مشروعك؟" أو "هل لديك تفاصيل أخرى تود مشاركتها؟").
3. ممنوع نهائياً إضافة أو تعقيب أي كلام مكرر أو أسئلة توجيهية زائفة في نهاية كل رسالة (Do NOT append trailing questions, project status checks, or boilerplate context summaries to your messages).
4. عند السلام أو الدردشة البسيطة العادية (مثل: "عامل ايه"، "ازيك"، "صباح الخير"، "شكرا"): رد في جملة واحدة مختصرة جداً وبشكل طبيعي كإنسان طبيعي (مثال: "تمام الحمد لله يا هندسة، أخبارك إيه؟") دون فتح سيرة المشروع ودون إضافة أي أسئلة زائدة عن المشروع، إلا إذا كان العميل هو من بدأ بالحديث عن تفاصيل أو أسئلة بالمشروع.
5. خط الدورة المعتمد للمشروع (AI PIPELINE STAGES & MANDATORY TOOL CALLS):
   - مرحلة `GREETING`: رد بسيط ومباشر دون فتح مواضيع مشروع تلقائياً.
   - مرحلة `DISCOVERY`: عند مناقشة تفاصيل الفكرة، يجب استدعاء أداة `update_context` بتمرير `current_stage` = 'DISCOVERY' وحفظ قائمة `pending_features` والـ `goal`.
   - مرحلة `VALUATION`: قبل إعطاء أي سعر، يجب اتباع الخطوات الآتية بالترتيب:
     أ) حدد نوع المشروع بدقة: هل هو بسيط (CRUD / Todo / صفحة بسيطة) أم متوسط (Web App / MVP) أم متقدم (E-Commerce / CRM / ERP) أم موبايل (Android / iOS)؟
     ب) اعرض قائمة الميزات المتفق عليها مع ساعات العمل التقريبية لكل ميزة بشكل منفصل.
     ج) احسب التكلفة بشكل تراكمي: ساعات العمل × سعر الساعة. لا تعطِ رقماً عشوائياً من عندك.
     د) أسعار مرجعية يجب التزامها:
        - Todo App / CRUD بسيط: بين 1,500 و 4,000 جنيه كحد أقصى.
        - Web App متوسط (MVP بميزات متعددة): بين 5,000 و 15,000 جنيه.
        - تطبيق موبايل Android/iOS: من 30,000 جنيه فأكثر.
        - E-Commerce / CRM / ERP: من 20,000 جنيه فأكثر حسب التعقيد.
     هـ) **لا تبالغ في السعر لمشروع بسيط** — التطبيق الذي يحتوي فقط على إضافة/حذف/تعديل/عرض مهام هو مشروع تدريبي بسيط لا يستحق أكثر من 2,000 إلى 3,000 جنيه.
     و) استدعِ `update_context` بقيمة `current_stage` = 'VALUATION' بعد عرض التسعير التفصيلي.
   - مرحلة `PROPOSAL`: عندما يوافق العميل أو يطلب بدء الديل/العمل/العقد/تعديل الميزانية، **يجب فوراً وبشكل إجباري استدعاء أداة `create_contract`** بالمبلغ النهائي الدقيق المتفق عليه مع العميل في المحادثة. وعندما ترجع الأداة رابط `contract_url` يجب أن تضمن الرابط الحقيقي داخل الرسالة.
   - ملاحظة هامة جداً: إذا سأل العميل أو استفسر عن كيفية حساب التكلفة (مثال: "ازاي"، "ليه السعر ده")، اشرح له التفاصيل المنطقية للتسعير بوضوح تام، **ولا تقم باستدعاء أداة `create_contract` مجدداً** طالما لم يطلب هو إنشاء عقد جديد، بل اكتفِ بالشرح والإقناع.
   - مرحلة `EXECUTION`: بعد توقيع العقد وسداد الدفعة الأولى، سيتولى النظام العمل تلقائياً.
   - إياك أن توافق شفهياً على الاتفاق المالي لأول مرة دون استدعاء أداة `create_contract`، ولكن لا تكرر استدعاءها في كل رد إذا كان العميل يتناقش معك.
6. لا تكرر نفسك، وتذكر آخر الحوارات وسياق المشروع المحفوظ.
7. أداة الذاكرة الممتدة (EXTENDED MEMORY TOOL):
   - الـ Context المتاح لك يشمل آخر 15 رسالة فقط. إذا أشار العميل لشيء قيل قبلها (ميزانية قديمة، فيتشر ذكره من قبل، موعد قيل مسبقاً)، **يجب فوراً استدعاء أداة `search_conversation_history`** بكلمة مفتاحية مناسبة قبل أن ترد.
   - لا تقل أبداً "لا أتذكر" أو "لم يُذكر هذا سابقاً" دون أن تستدعي `search_conversation_history` أولاً.
   - مثال: العميل يقول "قلتلك قبل كده السعر كان X" → استدعِ البحث بـ query="السعر" أو query="budget" واعرض ما وجدته.

Project Memory:
{$projectMemoryJson}

Conversation Memory:
{$conversationMemoryJson}
PROMPT;

        // ── 5. Load conversation history (single optimised query) ────────────────
        // We fetch the last 15 messages ordered ascending so history is chronological.
        $recentDiscussions = ProjectComment::where('project_id', $project->id)
            ->latest()
            ->take(15)
            ->get()
            ->reverse()
            ->values();

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
                // Build messages array — history + CURRENT message (fix #1)
                $openAiMessages = [['role' => 'system', 'content' => $systemPrompt]];

                foreach ($recentDiscussions as $comm) {
                    $openAiMessages[] = [
                        'role'    => $comm->author_id ? 'user' : 'assistant',
                        'content' => $this->sanitizeInput($comm->body),
                    ];
                }

                // ✅ FIX #1: append the current user message that was missing
                $openAiMessages[] = ['role' => 'user', 'content' => $cleanBody];

                $openAiTools = $this->formatOpenAiTools();

                $payload = [
                    'model'       => $openAiModel,
                    'messages'    => $openAiMessages,
                    'temperature' => 0.5,
                ];
                if (!empty($openAiTools)) {
                    $payload['tools']       = $openAiTools;
                    $payload['tool_choice'] = 'auto';
                }

                // ✅ FIX #4: Http::retry for transient network/rate errors
                $response = Http::withoutVerifying()
                    ->retry(2, 600, fn (\Throwable $e, $response) => $this->isRetryable($response))
                    ->timeout(40)
                    ->withToken($openAiKey)
                    ->post('https://api.openai.com/v1/chat/completions', $payload);

                if ($response->successful()) {
                    $activeModelUsed = $openAiModel;
                    $this->accumulateUsage($response->json('usage') ?? [], $totalPromptTokens, $totalCompletionTokens);

                    $choice = $response->json('choices.0.message');

                    // ✅ FIX #2: multi-turn tool calling while-loop
                    $iterations = 0;
                    while (!empty($choice['tool_calls']) && is_array($choice['tool_calls']) && $iterations < self::MAX_TOOL_ITERATIONS) {
                        $iterations++;

                        // Append assistant's tool-call message to conversation
                        $openAiMessages[] = $choice;

                        foreach ($choice['tool_calls'] as $toolCall) {
                            $fnName = $toolCall['function']['name'] ?? '';
                            $fnArgs = json_decode($toolCall['function']['arguments'] ?? '{}', true) ?: [];

                            Log::info("[AI Orchestrator] OpenAI tool call: {$fnName}", [
                                'project_id' => $project->id,
                                'args'       => $fnArgs,
                                'iteration'  => $iterations,
                            ]);

                            $tool = $this->toolRegistry->getTool($fnName);
                            $res  = $tool ? $tool->execute($project, $fnArgs) : ['error' => "Unknown tool: {$fnName}"];
                            $executedTools[] = ['tool' => $fnName, 'result' => $res];

                            // Append tool result
                            $openAiMessages[] = [
                                'role'         => 'tool',
                                'tool_call_id' => $toolCall['id'] ?? '',
                                'content'      => json_encode($res),
                            ];
                        }

                        // Re-call OpenAI after tool results
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
                            Log::error('[AI Orchestrator] OpenAI tool follow-up failed: ' . $followUp->body());
                            break;
                        }

                        $this->accumulateUsage($followUp->json('usage') ?? [], $totalPromptTokens, $totalCompletionTokens);
                        $choice = $followUp->json('choices.0.message');
                    }

                    // Final text reply (no more tool_calls)
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
                    'trace'      => $e->getTraceAsString(),
                ]);
            }
        }

        // ── 8. Gemini Fallback Provider ──────────────────────────────────────────
        if (empty($aiReplyText) && !empty($geminiKey)) {
            try {
                // Gemini requires alternating user/model turns
                $geminiContents = [
                    ['role' => 'user',  'parts' => [['text' => $systemPrompt]]],
                    ['role' => 'model', 'parts' => [['text' => 'فهمت التعليمات، أنا جاهز أساعد العميل.']]],
                ];

                foreach ($recentDiscussions as $comm) {
                    $geminiContents[] = [
                        'role'  => $comm->author_id ? 'user' : 'model',
                        'parts' => [['text' => $this->sanitizeInput($comm->body)]],
                    ];
                }

                // ✅ FIX #1: current message appended to Gemini too (was already there, kept for parity)
                $geminiContents[] = ['role' => 'user', 'parts' => [['text' => $cleanBody]]];

                $geminiTools = [];
                foreach ($this->toolRegistry->all() as $tool) {
                    $geminiTools[] = [
                        'name'        => $tool->name(),
                        'description' => $tool->description(),
                        'parameters'  => $tool->parameters(),
                    ];
                }

                $geminiPayload = [
                    'contents'         => $geminiContents,
                    'generationConfig' => ['temperature' => 0.5],
                ];
                if (!empty($geminiTools)) {
                    $geminiPayload['tools'] = [['function_declarations' => $geminiTools]];
                }

                // ✅ FIX #4: retry for Gemini
                $response = Http::withoutVerifying()
                    ->retry(2, 600, fn (\Throwable $e, $response) => $this->isRetryable($response))
                    ->timeout(35)
                    ->post("https://generativelanguage.googleapis.com/v1beta/models/{$geminiModel}:generateContent?key={$geminiKey}", $geminiPayload);

                if ($response->successful()) {
                    $activeModelUsed = $geminiModel;
                    $this->accumulateGeminiUsage($response->json('usageMetadata') ?? [], $totalPromptTokens, $totalCompletionTokens);

                    $candidate = $response->json('candidates.0.content') ?? [];
                    $parts     = $candidate['parts'] ?? [];

                    // ✅ FIX #2: Gemini multi-turn tool calling while-loop
                    $iterations = 0;
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

                            Log::info("[AI Orchestrator] Gemini tool call: {$fnName}", [
                                'project_id' => $project->id,
                                'args'       => $fnArgs,
                                'iteration'  => $iterations,
                            ]);

                            $tool = $this->toolRegistry->getTool($fnName);
                            $res  = $tool ? $tool->execute($project, $fnArgs) : ['error' => "Unknown tool: {$fnName}"];
                            $executedTools[] = ['tool' => $fnName, 'result' => $res];

                            // Append model's function-call turn + user's function-response turn
                            $geminiContents[] = ['role' => 'model', 'parts' => $parts];
                            $geminiContents[] = [
                                'role'  => 'user',
                                'parts' => [['functionResponse' => ['name' => $fnName, 'response' => $res]]],
                            ];
                        }

                        if (!$hasFunctionCall) {
                            // No more function calls — extract text and exit loop
                            $aiReplyText = $parts[0]['text'] ?? '';
                            break;
                        }

                        // Re-call Gemini
                        $followUp = Http::withoutVerifying()
                            ->retry(2, 600, fn (\Throwable $e, $response) => $this->isRetryable($response))
                            ->timeout(35)
                            ->post("https://generativelanguage.googleapis.com/v1beta/models/{$geminiModel}:generateContent?key={$geminiKey}", [
                                'contents'         => $geminiContents,
                                'generationConfig' => ['temperature' => 0.5],
                            ]);

                        if (!$followUp->successful()) {
                            Log::error('[AI Orchestrator] Gemini tool follow-up failed: ' . $followUp->body());
                            break;
                        }

                        $this->accumulateGeminiUsage($followUp->json('usageMetadata') ?? [], $totalPromptTokens, $totalCompletionTokens);
                        $candidate = $followUp->json('candidates.0.content') ?? [];
                        $parts     = $candidate['parts'] ?? [];
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
                    'trace'      => $e->getTraceAsString(),
                ]);
            }
        }

        // ── 9. Connection Error Fallback ─────────────────────────────────────────
        if (empty($aiReplyText)) {
            $aiReplyText = 'عذراً، حدث انقطاع مؤقت في الاتصال بخدمة الذكاء الاصطناعي. يرجى إعادة إرسال رسالتك مرة أخرى.';
        }

        // ── 10. Post-Response: Billing + Persistence inside one Transaction ───────
        // ✅ FIX #3: DB::transaction wraps both billing and comment creation
        $billedAmount   = 0.0;
        $currencySymbol = 'EGP';

        DB::transaction(function () use (
            $project, $aiReplyText, $totalPromptTokens, $totalCompletionTokens,
            $activeModelUsed, &$billedAmount, &$currencySymbol
        ) {
            // Billing
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

            // Save AI response
            ProjectComment::create([
                'project_id'       => $project->id,
                'author_id'        => null,
                'guest_name'       => 'AI Project Manager',
                'body'             => $aiReplyText,
                'commentable_type' => Project::class,
                'commentable_id'   => $project->id,
            ]);
        });

        // ── 11. Detailed structured log ──────────────────────────────────────────
        $durationMs = round((microtime(true) - $startTime) * 1000);
        Log::info('[AI Orchestrator] Completed', [
            'project_id'       => $project->id,
            'model'            => $activeModelUsed,
            'prompt_tokens'    => $totalPromptTokens,
            'completion_tokens'=> $totalCompletionTokens,
            'executed_tools'   => array_column($executedTools, 'tool'),
            'billed_amount'    => $billedAmount,
            'currency'         => $currencySymbol,
            'duration_ms'      => $durationMs,
            'reply_length'     => mb_strlen($aiReplyText),
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
     * Sanitize and size-limit a user/history message.
     *
     * Removes HTML tags, null bytes, dangerous control characters, and trims to MAX_MESSAGE_CHARS.
     */
    private function sanitizeInput(string $raw): string
    {
        // 1. Strip HTML
        $text = strip_tags($raw);

        // 2. Remove null bytes and dangerous control characters (keep \t \n \r)
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text);

        // 3. Ensure valid UTF-8 (replace malformed sequences)
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');

        // 4. Enforce max length
        $text = mb_substr(trim($text), 0, self::MAX_MESSAGE_CHARS);

        return $text;
    }

    /**
     * Accumulate OpenAI usage tokens into running totals.
     */
    private function accumulateUsage(array $usage, int &$prompt, int &$completion): void
    {
        $prompt     += (int) ($usage['prompt_tokens']     ?? 0);
        $completion += (int) ($usage['completion_tokens'] ?? 0);
    }

    /**
     * Accumulate Gemini usage tokens into running totals.
     */
    private function accumulateGeminiUsage(array $meta, int &$prompt, int &$completion): void
    {
        $prompt     += (int) ($meta['promptTokenCount']     ?? 0);
        $completion += (int) ($meta['candidatesTokenCount'] ?? 0);
    }

    /**
     * Determine whether an HTTP response/exception warrants a retry.
     * Retries on: 429, 500, 502, 503, 504, and connection exceptions.
     */
    private function isRetryable(mixed $response): bool
    {
        if ($response instanceof \Throwable) {
            return true; // network/connection errors
        }
        return in_array($response?->status(), [429, 500, 502, 503, 504], true);
    }

    /**
     * Format registered tools into OpenAI function declaration schema.
     */
    protected function formatOpenAiTools(): array
    {
        $openAiTools = [];
        foreach ($this->toolRegistry->all() as $tool) {
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
