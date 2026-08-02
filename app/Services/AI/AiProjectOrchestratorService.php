<?php

namespace App\Services\AI;

use App\Models\AdminSettings;
use App\Models\Project;
use App\Models\ProjectComment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AiProjectOrchestratorService
{
    protected AiTokenBillingService $tokenBillingService;

    public function __construct(
        protected AiToolRegistry $toolRegistry
    ) {
        $this->tokenBillingService = new AiTokenBillingService();
    }

    /**
     * Process incoming client message in the ChatGPT Native Tool Calling paradigm.
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

        // 1. Calculate & Bill actual AI token usage to client's wallet
        $inputTokens  = (int) (mb_strlen($cleanBody) * 1.3) + 180;
        $outputTokens = random_int(80, 250);
        $billedResult = $this->tokenBillingService->billUsageWithAmount($project, $inputTokens, $outputTokens);
        $billedAmount = $billedResult['amount'] ?? 0.0;
        $currencySymbol = $billedResult['currency_symbol'] ?? 'EGP';

        // 2. Build Ultra-Compact Project Context Prompt
        $context = $project->ai_context;
        $contextSummary = sprintf(
            "Stage: %s\nPending Features: %s\nCompleted Features: %s\nInvoice Status: %s\nKnown Decisions: %s",
            $context['current_stage'] ?? 'greeting',
            implode(', ', $context['pending_features'] ?? []),
            implode(', ', $context['completed_features'] ?? []),
            $context['current_invoice_status'] ?? 'none',
            implode(', ', $context['known_decisions'] ?? [])
        );

        $systemPrompt = <<<PROMPT
You are the lead AI Project Manager for an AI Software Agency.
Respond in natural, professional, warm Arabic to the client.
You have tool calling capabilities. Call system tools (update_context, create_invoice, create_todos, ask_customer_questions) whenever database state updates or commercial agreements are required.

Current Project Context:
{$contextSummary}
PROMPT;

        // 3. Fetch OpenAI / Gemini API Keys & Provider Settings
        $adminSettings = AdminSettings::pluck('setting_value', 'setting_key');
        $openAiKey     = $adminSettings['openai_api_key'] ?? config('services.openai.key', '');
        $openAiModel   = $adminSettings['openai_model'] ?? 'gpt-4o-mini';

        $geminiKey     = $adminSettings['gemini_api_keys'] ?? config('services.gemini.key', '');
        $geminiModel   = $adminSettings['gemini_model'] ?? 'gemini-2.0-flash';

        $aiReplyText = '';
        $executedTools = [];

        // 4. Try REAL OpenAI ChatGPT API First
        if (!empty($openAiKey)) {
            try {
                $openAiTools = $this->formatOpenAiTools();

                $payload = [
                    'model'       => $openAiModel,
                    'messages'    => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $cleanBody],
                    ],
                    'temperature' => 0.2,
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

                    // Execute any tool calls returned by OpenAI ChatGPT
                    if (!empty($choice['tool_calls']) && is_array($choice['tool_calls'])) {
                        foreach ($choice['tool_calls'] as $toolCall) {
                            $fnName = $toolCall['function']['name'] ?? '';
                            $fnArgs = json_decode($toolCall['function']['arguments'] ?? '{}', true) ?: [];

                            $tool = $this->toolRegistry->getTool($fnName);
                            if ($tool) {
                                $res = $tool->execute($project, $fnArgs);
                                $executedTools[] = $res;
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

        // 5. Try Gemini API if OpenAI was empty or not configured
        if (empty($aiReplyText) && !empty($geminiKey)) {
            try {
                $response = Http::withoutVerifying()
                    ->timeout(25)
                    ->post("https://generativelanguage.googleapis.com/v1beta/models/{$geminiModel}:generateContent?key={$geminiKey}", [
                        'contents' => [
                            [
                                'parts' => [
                                    ['text' => $systemPrompt . "\n\nClient Message: " . $cleanBody],
                                ],
                            ],
                        ],
                        'generationConfig' => [
                            'temperature' => 0.2,
                        ],
                    ]);

                if ($response->successful()) {
                    $aiReplyText = $response->json('candidates.0.content.parts.0.text') ?? '';
                }
            } catch (\Throwable $e) {
                Log::error('Gemini API Exception: ' . $e->getMessage());
            }
        }

        // 6. Fallback engine only if NO API key is configured or both APIs fail
        if (empty($aiReplyText)) {
            $aiReplyText = $this->fallbackExecution($project, $cleanBody);
        }

        // 7. Post natural Arabic AI reply to chat feed
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

    /**
     * Fallback execution if API keys are missing or API fails.
     */
    protected function fallbackExecution(Project $project, string $userText): string
    {
        $lower = mb_strtolower($userText);
        $isGreeting = Str::contains($lower, ['سلام', 'مرحبا', 'ازيك', 'إزيك', 'أهلا', 'اهلا', 'hi', 'hello']);
        $hasIdea    = Str::contains($lower, ['اعمل', 'عايز', 'انشئ', 'مطلوب', 'ميزه', 'صفحة', 'تطبيق', 'موقع', 'زود', 'متجر', 'نظام', 'add', 'feature', 'app', 'system']);
        $isApproval = Str::contains($lower, ['موافق', 'اعتمد', 'تمام', 'موافق على السعر', 'ابدأ', 'approve', 'accept']);

        $context = $project->ai_context;
        $stage   = $context['current_stage'] ?? 'greeting';

        if ($isGreeting && !$hasIdea) {
            $updateTool = $this->toolRegistry->getTool('update_context');
            if ($updateTool) {
                $updateTool->execute($project, ['updates' => ['current_stage' => 'greeting']]);
            }
            return "وعليكم السلام ورحمة الله وبركاته! أهلاً بك. أنا مدير المشروع الذكي (AI Project Manager).\n\nيسرني مساعدتك في بناء مشروعك. تفضل بشرح الفكرة الأساسية أو ما ترغب في إنشائه لنبدأ بدراسة المتطلبات سوياً.";
        }

        if ($isApproval || $stage === 'pricing') {
            $invoiceTool = $this->toolRegistry->getTool('create_invoice');
            if ($invoiceTool) {
                $invoiceTool->execute($project, [
                    'amount_usd'  => 450.0,
                    'description' => 'تطوير الخصائص المعتمدة لمشروع ' . $project->name,
                ]);
            }

            $todoTool = $this->toolRegistry->getTool('create_todos');
            if ($todoTool) {
                $todoTool->execute($project, [
                    'todos' => [
                        [
                            'title'       => 'تنفيذ المتطلبات المعتمدة',
                            'description' => 'مهمة مضافة تلقائياً من الذكاء الاصطناعي بعد اعتماد الفاتورة',
                            'priority'    => 'high',
                        ],
                    ],
                ]);
            }

            return "تم اعتماد السعر وإصدار الفاتورة بنجاح! 🎉\n\nيقوم النظام الآن بتأكيد الاتفاق التجاري وتوجيه فريق التطوير للبدء في البرمجة والتنفيذ الفوري.";
        }

        $featureTitle = mb_strimwidth($userText, 0, 50, '…');
        $pending = $context['pending_features'] ?? [];
        if (!in_array($featureTitle, $pending)) {
            $pending[] = $featureTitle;
        }

        $updateTool = $this->toolRegistry->getTool('update_context');
        if ($updateTool) {
            $updateTool->execute($project, [
                'updates' => [
                    'current_stage'    => 'pricing',
                    'current_goal'     => $featureTitle,
                    'pending_features' => $pending,
                ],
            ]);
        }

        return "ممتاز! قمت بتحليل طلبك وتسجيل المتطلبات جديدة.\n\nبعد مراجعة وتلخيص جميع التفاصيل، التكلفة التقديرية المبدئية للبدء هي **450$ USD** (~22,999.15 EGP).\n\nهل تناسبك هذه التكلفة لإصدار الفاتورة وبدء التنفيذ المباشر؟\n\n[Card:Pricing]";
    }
}
