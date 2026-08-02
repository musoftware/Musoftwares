<?php

namespace App\Services\AI;

use App\Models\Project;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AiAgencyStructuredEngine
{
    /**
     * Thinking Phase: Calls LLM with Project Context + latest message and returns structured JSON decision.
     */
    public function think(Project $project, string $userText, array $recentMessages = []): array
    {
        $adminSettings = \App\Models\AdminSettings::pluck('setting_value', 'setting_key');
        $apiKey = $adminSettings['gemini_api_keys'] ?? config('services.gemini.key', '');
        $model  = $adminSettings['gemini_model'] ?? 'gemini-2.0-flash';

        $context = $project->ai_context;

        $systemPrompt = <<<PROMPT
You are the AI Project Manager & Lead Analyst for an elite AI Software Agency.
Your job is to interact warmly in natural Arabic with the client, analyze their requests incrementally, update project context, and specify precise execution decisions.

CRITICAL ARCHITECTURE RULES:
1. Do NOT store or invent any "project budget". Price is defined ONLY when creating an invoice for a specific approved scope.
2. Maintain a single lifetime Project Context. Do NOT reset completed features or existing history.
3. Compare new requests against "completed_features". Generate "tasks_to_create" ONLY for new missing additions. Never touch or edit completed tasks.
4. Output MUST be strictly valid JSON matching this schema:

{
  "reply": "Conversational Arabic message responding directly to client",
  "need_more_questions": ["Question 1?"],
  "context_updates": {
    "current_stage": "greeting|discovery|requirements|pricing|invoice|execution|completed",
    "current_goal": "Summary of current goal",
    "pending_features": ["New feature name"],
    "completed_features": ["Existing done feature"],
    "tech_stack": ["React", "Laravel"],
    "known_decisions": ["Uses Stripe"]
  },
  "create_invoice": {
    "should_create": false,
    "amount_usd": 0.0,
    "description": "Invoice description for approved scope"
  },
  "tasks_to_create": [
    {
      "title": "Specific developer task title",
      "description": "Description of work needed",
      "priority": "high|medium|low"
    }
  ]
}

Current Project Context (JSON):
PROMPT;

        $contextJson = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $userPrompt  = "Project Context:\n{$contextJson}\n\nClient Latest Message: \"{$userText}\"";

        if (!empty($apiKey)) {
            try {
                $response = Http::withoutVerifying()
                    ->timeout(25)
                    ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}", [
                        'contents' => [
                            [
                                'parts' => [
                                    ['text' => $systemPrompt . "\n\n" . $userPrompt],
                                ],
                            ],
                        ],
                        'generationConfig' => [
                            'responseMimeType' => 'application/json',
                            'temperature'      => 0.2,
                        ],
                    ]);

                if ($response->successful()) {
                    $rawText = $response->json('candidates.0.content.parts.0.text') ?? '';
                    $parsed  = json_decode($rawText, true);
                    if (is_array($parsed) && isset($parsed['reply'])) {
                        return $parsed;
                    }
                }
            } catch (\Throwable $e) {
                // Fallback to local heuristic engine
            }
        }

        // Local Rule-Based Fallback Engine if API key is not configured or fails
        return $this->fallbackThinkingEngine($project, $userText, $context);
    }

    /**
     * High-fidelity rule-based fallback decision engine.
     */
    protected function fallbackThinkingEngine(Project $project, string $userText, array $context): array
    {
        $lower = mb_strtolower($userText);
        $isGreeting = Str::contains($lower, ['سلام', 'مرحبا', 'ازيك', 'إزيك', 'أهلا', 'اهلا', 'hi', 'hello']);
        $hasIdea    = Str::contains($lower, ['اعمل', 'عايز', 'انشئ', 'مطلوب', 'ميزه', 'صفحة', 'تطبيق', 'موقع', 'زود', 'متجر', 'نظام', 'add', 'feature', 'app', 'system']);
        $isApproval = Str::contains($lower, ['موافق', 'اعتمد', 'تمام', 'موافق على السعر', 'ابدأ', 'approve', 'accept']);

        $stage = $context['current_stage'] ?? 'greeting';

        if ($isGreeting && !$hasIdea) {
            return [
                'reply'               => "وعليكم السلام ورحمة الله وبركاته! أهلاً بك. أنا مدير المشروع الذكي (AI Project Manager).\n\nيسرني مساعدتك في بناء مشروعك. تفضل بشرح الفكرة الأساسية أو ما ترغب في إنشائه لنبدأ بدراسة المتطلبات سوياً.",
                'need_more_questions' => [],
                'context_updates'     => [
                    'current_stage' => 'greeting',
                ],
                'create_invoice'      => ['should_create' => false, 'amount_usd' => 0.0, 'description' => ''],
                'tasks_to_create'     => [],
            ];
        }

        if ($isApproval || $stage === 'pricing') {
            return [
                'reply'               => "تم اعتماد السعر والفاتورة بنجاح! 🎉\n\nيقوم النظام الآن بإصدار الفاتورة وتأكيد الاتفاق التجاري، وسيتم توليد مهام التطوير التنفيذية للمبرمج للبدء فوراً.",
                'need_more_questions' => [],
                'context_updates'     => [
                    'current_stage'          => 'execution',
                    'current_invoice_status' => 'pending',
                ],
                'create_invoice'      => [
                    'should_create' => true,
                    'amount_usd'    => 450.0,
                    'description'   => 'تطوير المتطلبات والخصائص المعتمدة لمشروع ' . $project->name,
                ],
                'tasks_to_create'     => [
                    [
                        'title'       => 'تنفيذ متطلبات المشروع المعتمدة',
                        'description' => 'مهمة مضافة تلقائياً من الـ AI بعد اعتماد الفاتورة',
                        'priority'    => 'high',
                    ],
                ],
            ];
        }

        $featureTitle = mb_strimwidth($userText, 0, 50, '…');
        $pending      = $context['pending_features'] ?? [];
        if (!in_array($featureTitle, $pending)) {
            $pending[] = $featureTitle;
        }

        return [
            'reply'               => "ممتاز! قمت بتحليل طلبك وتسجيل المتطلبات جديدة.\n\nبعد مراجعة وتلخيص جميع التفاصيل، التكلفة التقديرية المبدئية للبدء هي **450$ USD** (~22,999.15 EGP).\n\nهل تناسبك هذه التكلفة لإصدار الفاتورة وبدء التنفيذ المباشر؟\n\n[Card:Pricing]",
            'need_more_questions' => ['هل يحتاج المشروع لوحة تحكم مخصصة؟'],
            'context_updates'     => [
                'current_stage'    => 'pricing',
                'current_goal'     => $featureTitle,
                'pending_features' => $pending,
            ],
            'create_invoice'      => ['should_create' => false, 'amount_usd' => 0.0, 'description' => ''],
            'tasks_to_create'     => [],
        ];
    }
}
