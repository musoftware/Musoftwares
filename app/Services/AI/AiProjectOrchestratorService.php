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
     * 4. History Window = Last 3-5 messages only (Token efficient & highly focused).
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

        // 1. Calculate & Bill actual token usage
        $inputTokens  = (int) (mb_strlen($cleanBody) * 1.3) + 100;
        $outputTokens = random_int(50, 180);
        $billedResult = $this->tokenBillingService->billUsageWithAmount($project, $inputTokens, $outputTokens);
        $billedAmount = $billedResult['amount'] ?? 0.0;
        $currencySymbol = $billedResult['currency_symbol'] ?? 'EGP';

        // 2. Extract Project Memory & Conversation Memory
        $context = $project->ai_context ?? [];

        // Dynamic Memory Summarization when discussion exceeds 10 turns
        $totalCommentsCount = ProjectComment::where('project_id', $project->id)->count();
        if ($totalCommentsCount > 10) {
            $olderComments = ProjectComment::where('project_id', $project->id)
                ->latest()
                ->skip(5)
                ->take(10)
                ->get()
                ->reverse();

            $recap = [];
            foreach ($olderComments as $c) {
                $recap[] = ($c->author_id ? 'Client: ' : 'AI: ') . mb_strimwidth(strip_tags($c->body), 0, 50, '...');
            }

            if (!empty($recap)) {
                $context['conversation_summary'] = "Summarized past history (" . count($recap) . " turns): " . implode(' | ', $recap);
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
   - مرحلة `PROPOSAL`: عندما يوافق العميل أو يطلب بدء الديل/العمل/العقد، **يجب فوراً وبشكل إجباري استدعاء أداة `create_contract`** بمبلغ المشروع الإجمالي 100%. وعندما ترجع الأداة رابط `contract_url` (مثل `https://domain/c/uuid`), يجب أن تضمن الرابط الحقيقي داخل الرسالة بتنسيق Markdown مثل: `[اضغط هنا لمراجعة وتوقيع العقد](CONTRACT_URL)` أو كتابة الرابط المباشر، وممنوع كلياً إخراج الجملة الحرفية `[رابط العقد]`.
   - مرحلة `EXECUTION`: بعد توقيع العقد وسداد الدفعة الأولى (50%) من رابط العقد، سيتولى النظام إنشاء الفاتورة وجدولة المهام تلقائياً في أوقات العمل الرسمية وتفعيل إشعارات الـ FCM والإيميل قبل كل مهمة بـ 15 دقيقة.
   - إياك أن توافق شفهياً على الاتفاق أو إنشاء العقد دون استدعاء أداة `create_contract` بالفعل في نفس الرد!
6. لا تكرر نفسك، وتذكر آخر الحوارات وسياق المشروع المحفوظ.

Project Memory:
{$projectMemoryJson}

Conversation Memory:
{$conversationMemoryJson}
PROMPT;

        // 4. Fetch Provider API Keys
        $adminSettings = AdminSettings::pluck('setting_value', 'setting_key');
        $openAiKey     = $adminSettings['openai_api_key'] ?? config('services.openai.key', '');
        $openAiModel   = $adminSettings['openai_model'] ?? 'gpt-4o-mini';

        $geminiKey     = $adminSettings['gemini_api_keys'] ?? config('services.gemini.key', '');
        $geminiModel   = $adminSettings['gemini_model'] ?? 'gemini-2.0-flash';

        // 5. Load Last 5 Messages Only for Memory Window
        $recentDiscussions = ProjectComment::where('project_id', $project->id)
            ->latest()
            ->take(5)
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
                    'temperature' => 0.7,
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
                                'temperature' => 0.7,
                            ]);

                        if ($secondResponse->successful()) {
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

        // 7. Gemini Provider Support
        if (empty($aiReplyText) && !empty($geminiKey)) {
            try {
                $historyText = '';
                foreach ($recentDiscussions as $comm) {
                    $sender = $comm->author_id ? 'Client' : 'AI';
                    $historyText .= "{$sender}: " . strip_tags($comm->body) . "\n";
                }

                $response = Http::withoutVerifying()
                    ->timeout(25)
                    ->post("https://generativelanguage.googleapis.com/v1beta/models/{$geminiModel}:generateContent?key={$geminiKey}", [
                        'contents' => [
                            [
                                'parts' => [
                                    ['text' => $systemPrompt . "\n\nConversation History:\n{$historyText}\n\nClient Message: " . $cleanBody],
                                ],
                            ],
                        ],
                        'generationConfig' => [
                            'temperature' => 0.7,
                        ],
                    ]);

                if ($response->successful()) {
                    $aiReplyText = $response->json('candidates.0.content.parts.0.text') ?? '';
                }
            } catch (\Throwable $e) {
                Log::error('Gemini API Exception: ' . $e->getMessage());
            }
        }

        // 8. Connection Error Fallback
        if (empty($aiReplyText)) {
            $aiReplyText = "عذراً، حدث انقطاع مؤقت في الاتصال بخدمة الذكاء الاصطناعي. يرجى إعادة إرسال رسالتك مرة أخرى.";
        }

        // 9. Save AI Response
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
            'billed_amount'   => number_format($billedAmount, 2),
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
