<?php

namespace App\Services\AI;

use App\Models\AdminSettings;
use App\Models\Project;
use App\Models\ProjectComment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiProjectOrchestratorService
{
    protected AiTokenBillingService $tokenBillingService;

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
     * 4. History Window = Last 15 messages (enough context for a real conversation without hitting token limits).
     * 5. Tool Calling = OpenAI natively triggers update_context, create_invoice, create_todos, ask_customer_questions.
     * 6. Zero Laravel String Rules (Laravel is purely stateless execution & memory persistence).
     */
    public function processClientMessage(Project $project, string $messageBody, int $authorId): array
    {
        if (str_starts_with(trim($messageBody), '[System:')) {
            return ['ok' => true, 'billed' => 0];
        }

        if (!$project->ai_enabled) {
            $project->update(['ai_enabled' => true]);
        }

        $cleanBody = strip_tags($messageBody);

        $totalPromptTokens = 0;
        $totalCompletionTokens = 0;
        $activeModelUsed = 'gpt-4o-mini';

        // 2. Extract Project Memory & Conversation Memory
        $context = $project->ai_context ?? [];

        // Dynamic Memory Summarization when discussion exceeds 10 turns
        $totalCommentsCount = ProjectComment::where('project_id', $project->id)->count();
        if ($totalCommentsCount > 20) {
            $olderComments = ProjectComment::where('project_id', $project->id)
                ->latest()
                ->skip(15)
                ->take(20)
                ->get()
                ->reverse();

            $recap = [];
            foreach ($olderComments as $c) {
                $recap[] = ($c->author_id ? 'Client: ' : 'AI: ') . mb_strimwidth(strip_tags($c->body), 0, 200, '...');
            }

            if (!empty($recap)) {
                $context['conversation_summary'] = "Summarized past history (" . count($recap) . " turns):\n" . implode("\n", $recap);
            }
        }

        $projectMemory = [
            'current_stage'      => $context['current_stage'] ?? 'GREETING',
            'goal'               => $context['goal'] ?? $project->project_name,
            'completed_features' => $context['completed_features'] ?? [],
            'pending_features'   => $context['pending_features'] ?? [],
            'tech_stack'         => $context['tech_stack'] ?? 'Laravel, React, Inertia',
            'invoice_status'     => $context['current_invoice_status'] ?? 'none',
        ];

        $conversationMemory = [
            'summary'     => $context['conversation_summary'] ?? 'Conversation initiated.',
            'waiting_for' => $context['waiting_for'] ?? [],
        ];

        $projectMemoryJson      = json_encode($projectMemory, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $conversationMemoryJson = json_encode($conversationMemory, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        // 3. Natural & Intelligent Egyptian Developer Persona System Prompt
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
   - مرحلة `VALUATION`: عند عرض التكلفة، احسب إجمالي ميزانية المشروع بالكامل (100%)، ووضح أن بدء العمل يتطلب سداد 50% كدفعة مقدمة لجدولة المهام وتوقيع العقد. استدعِ `update_context` بقيمة `current_stage` = 'VALUATION'.
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

        // 4. Fetch Provider API Keys (Priority: AdminSettings -> env/config)
        $adminSettings = AdminSettings::pluck('setting_value', 'setting_key');
        $openAiKey     = !empty($adminSettings['openai_api_key'])
            ? $adminSettings['openai_api_key']
            : config('services.openai.key');
        $openAiModel   = !empty($adminSettings['openai_model']) ? $adminSettings['openai_model'] : 'gpt-4o-mini';

        $geminiKey     = !empty($adminSettings['gemini_api_keys'])
            ? $adminSettings['gemini_api_keys']
            : config('services.gemini.key');
        $geminiModel   = !empty($adminSettings['gemini_model']) ? $adminSettings['gemini_model'] : 'gemini-2.0-flash';

        // 5. Load Last 15 Messages for Memory Window (gpt-4o-mini & gemini-2.0-flash handle large contexts cheaply)
        $recentDiscussions = ProjectComment::where('project_id', $project->id)
            ->latest()
            ->take(15)
            ->get()
            ->reverse();

        $openAiMessages = [
            ['role' => 'system', 'content' => $systemPrompt],
        ];

        foreach ($recentDiscussions as $comm) {
            $openAiMessages[] = [
                'role'    => $comm->author_id ? 'user' : 'assistant',
                'content' => strip_tags($comm->body),
            ];
        }

        $aiReplyText = '';
        $executedTools = [];

        // 6. Real OpenAI ChatGPT Execution
        if (!empty($openAiKey)) {
            try {
                $openAiTools = $this->formatOpenAiTools();

                $payload = [
                    'model'       => $openAiModel,
                    'messages'    => $openAiMessages,
                    'temperature' => 0.5,
                ];

                if (!empty($openAiTools)) {
                    $payload['tools'] = $openAiTools;
                    $payload['tool_choice'] = 'auto';
                }

                $response = Http::withoutVerifying()
                    ->timeout(30)
                    ->withToken($openAiKey)
                    ->post('https://api.openai.com/v1/chat/completions', $payload);

                if ($response->successful()) {
                    $activeModelUsed = $openAiModel;
                    $usage1 = $response->json('usage') ?? [];
                    $totalPromptTokens += (int) ($usage1['prompt_tokens'] ?? 0);
                    $totalCompletionTokens += (int) ($usage1['completion_tokens'] ?? 0);

                    $choice = $response->json('choices.0.message');
                    $aiReplyText = $choice['content'] ?? '';

                    // Execute tool calls natively
                    if (!empty($choice['tool_calls']) && is_array($choice['tool_calls'])) {
                        $openAiMessages[] = $choice;

                        foreach ($choice['tool_calls'] as $toolCall) {
                            $fnName = $toolCall['function']['name'] ?? '';
                            $fnArgs = json_decode($toolCall['function']['arguments'] ?? '{}', true) ?: [];

                            $tool = $this->toolRegistry->getTool($fnName);
                            if ($tool) {
                                $res = $tool->execute($project, $fnArgs);
                                $executedTools[] = $res;

                                $openAiMessages[] = [
                                    'role'         => 'tool',
                                    'tool_call_id' => $toolCall['id'] ?? '',
                                    'content'      => json_encode($res),
                                ];
                            }
                        }

                        // Get final natural text reply after tool execution
                        $secondResponse = Http::withoutVerifying()
                            ->timeout(30)
                            ->withToken($openAiKey)
                            ->post('https://api.openai.com/v1/chat/completions', [
                                'model'       => $openAiModel,
                                'messages'    => $openAiMessages,
                                'temperature' => 0.5,
                            ]);

                        if ($secondResponse->successful()) {
                            $usage2 = $secondResponse->json('usage') ?? [];
                            $totalPromptTokens += (int) ($usage2['prompt_tokens'] ?? 0);
                            $totalCompletionTokens += (int) ($usage2['completion_tokens'] ?? 0);

                            $secondChoiceText = $secondResponse->json('choices.0.message.content');
                            if (!empty($secondChoiceText)) {
                                $aiReplyText = $secondChoiceText;
                            }
                        }
                    }
                } else {
                    Log::error('OpenAI API Request Failed: ' . $response->body());
                }
            } catch (\Throwable $e) {
                Log::error('OpenAI ChatGPT Exception: ' . $e->getMessage());
            }
        }

        // 7. Gemini Provider Support (multi-turn chat format + function calling)
        if (empty($aiReplyText) && !empty($geminiKey)) {
            try {
                // Build proper multi-turn contents array (system prompt as first user turn)
                $geminiContents = [
                    ['role' => 'user', 'parts' => [['text' => $systemPrompt]]],
                    ['role' => 'model', 'parts' => [['text' => 'فهمت التعليمات، أنا جاهز أساعد العميل.']]],
                ];

                foreach ($recentDiscussions as $comm) {
                    $geminiContents[] = [
                        'role'  => $comm->author_id ? 'user' : 'model',
                        'parts' => [['text' => strip_tags($comm->body)]],
                    ];
                }

                // Append current message
                $geminiContents[] = ['role' => 'user', 'parts' => [['text' => $cleanBody]]];

                // Build Gemini-format tools declarations
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

                $response = Http::withoutVerifying()
                    ->timeout(25)
                    ->post("https://generativelanguage.googleapis.com/v1beta/models/{$geminiModel}:generateContent?key={$geminiKey}", $geminiPayload);

                if ($response->successful()) {
                    $activeModelUsed = $geminiModel;
                    $usageMeta = $response->json('usageMetadata') ?? [];
                    $totalPromptTokens += (int) ($usageMeta['promptTokenCount'] ?? 0);
                    $totalCompletionTokens += (int) ($usageMeta['candidatesTokenCount'] ?? 0);

                    $candidate = $response->json('candidates.0.content') ?? [];
                    $parts = $candidate['parts'] ?? [];

                    // Handle function calls from Gemini
                    $geminiCalledTools = false;
                    foreach ($parts as $part) {
                        if (!empty($part['functionCall'])) {
                            $geminiCalledTools = true;
                            $fnName = $part['functionCall']['name'] ?? '';
                            $fnArgs = $part['functionCall']['args'] ?? [];

                            $tool = $this->toolRegistry->getTool($fnName);
                            if ($tool) {
                                $res = $tool->execute($project, $fnArgs);
                                $executedTools[] = $res;

                                // Append function response and re-call Gemini for final text
                                $geminiContents[] = ['role' => 'model', 'parts' => $parts];
                                $geminiContents[] = [
                                    'role'  => 'user',
                                    'parts' => [['functionResponse' => ['name' => $fnName, 'response' => $res]]],
                                ];

                                $followUp = Http::withoutVerifying()
                                    ->timeout(25)
                                    ->post("https://generativelanguage.googleapis.com/v1beta/models/{$geminiModel}:generateContent?key={$geminiKey}", [
                                        'contents'         => $geminiContents,
                                        'generationConfig' => ['temperature' => 0.5],
                                    ]);

                                if ($followUp->successful()) {
                                    $followUsage = $followUp->json('usageMetadata') ?? [];
                                    $totalPromptTokens += (int) ($followUsage['promptTokenCount'] ?? 0);
                                    $totalCompletionTokens += (int) ($followUsage['candidatesTokenCount'] ?? 0);
                                    $aiReplyText = $followUp->json('candidates.0.content.parts.0.text') ?? '';
                                }
                            }
                            break; // Handle one function call per turn
                        }
                    }

                    if (!$geminiCalledTools) {
                        $aiReplyText = $parts[0]['text'] ?? '';
                    }
                }
            } catch (\Throwable $e) {
                Log::error('Gemini API Exception: ' . $e->getMessage());
            }
        }

        // 8. Connection Error Fallback
        if (empty($aiReplyText)) {
            $aiReplyText = "عذراً، حدث انقطاع مؤقت في الاتصال بخدمة الذكاء الاصطناعي. يرجى إعادة إرسال رسالتك مرة أخرى.";
        }

        // 9. Post-Response Real Token Billing
        $billedAmount = 0.0;
        $currencySymbol = 'EGP';
        if ($totalPromptTokens > 0 || $totalCompletionTokens > 0) {
            $billedResult = $this->tokenBillingService->billUsageWithAmount(
                $project,
                $totalPromptTokens,
                $totalCompletionTokens,
                $activeModelUsed,
                'AI Project Manager Interaction'
            );
            $billedAmount = (float) ($billedResult['amount'] ?? 0.0);
            $currencySymbol = $billedResult['currency_symbol'] ?? 'EGP';
        }

        // 10. Save AI Response
        ProjectComment::create([
            'project_id'       => $project->id,
            'author_id'        => null,
            'guest_name'       => 'AI Project Manager',
            'body'             => $aiReplyText,
            'commentable_type' => Project::class,
            'commentable_id'   => $project->id,
        ]);

        return [
            'ok'              => true,
            'billed_amount'   => number_format($billedAmount, 4),
            'currency_symbol' => $currencySymbol,
            'executed_tools'  => $executedTools,
        ];
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
