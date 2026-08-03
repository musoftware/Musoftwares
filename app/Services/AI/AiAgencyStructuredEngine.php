<?php

namespace App\Services\AI;

use App\Models\Project;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AiAgencyStructuredEngine
{
    protected AiResponseValidator   $validator;
    protected ScopePricingEngine    $pricingEngine;
    protected ConversationStateMachine $stateMachine;

    public function __construct()
    {
        $this->validator     = new AiResponseValidator();
        $this->pricingEngine = new ScopePricingEngine();
        $this->stateMachine  = new ConversationStateMachine();
    }

    /**
     * Thinking Phase: Single-Brain LLM Decision Engine.
     * The LLM is the sole decision maker for archetype, intent, scope changes, and completeness.
     */
    public function think(Project $project, string $userText, array $recentMessages = []): array
    {
        try {
            $adminSettings = \App\Models\AdminSettings::pluck('setting_value', 'setting_key');
        } catch (\Throwable $e) {
            $adminSettings = collect();
        }
        try {
            $apiKey = $adminSettings['gemini_api_keys'] ?? config('services.gemini.key', '');
        } catch (\Throwable $e) {
            $apiKey = '';
        }
        $model = $adminSettings['gemini_model'] ?? 'gemini-2.0-flash';

        $context = $project->ai_context ?? [];

        $systemPrompt = <<<PROMPT
You are the SINGLE INTELLIGENCE ENGINE & AI Project Manager for an elite AI Software Agency.
Your job is to understand natural Arabic user messages, evaluate project requirements, classify requests against the Project Snapshot, and manage the full agency workflow.

CRITICAL WORKFLOW & DECISION RULES:
1. ALWAYS READ THE FULL PROJECT SNAPSHOT (Summary, Approved Scope, Contract Status, Invoice 50% Payment, Support Status, Tasks/Todos, Company Policies).
2. QUESTION RULE: If information is incomplete during discovery, ask AT MOST ONE specific question in your reply. Do not overwhelm the client with lists of questions.
3. PRE-PAYMENT FLOW:
   - Understand requirements -> Summarize understanding -> Estimate price & duration -> Generate/Update Contract.
   - When Contract is Approved & 50% Deposit is Received (`is_50pct_paid`: true): Transition stage to EXECUTION and generate "admin_work" containing (summary, developer_tasks, admin_todos, developer_notes, suggested_priorities) for Admin review.
4. POST-DEVELOPMENT / IN-PROGRESS CLASSIFICATION (`request_classification`):
   - "QUESTION": Client asks general question -> Provide conversational reply.
   - "BUG": Client reports a bug -> Check `support_status`. If `is_active` is true -> generate free bug fix tasks. If expired -> generate paid maintenance quote.
   - "IN_SCOPE_FEATURE": Client requests a feature already inside `approved_scope` -> Generate developer tasks.
   - "CHANGE_REQUEST": Client requests a feature/change outside `approved_scope` -> Generate new price estimate & contract addendum.
5. CONFLICT & RECONCILIATION RULE: If the user changes request (e.g. from 'Corporate Website' to 'Mobile App'), set "conflict_detected": true and reset obsolete features.
6. Output MUST be strictly valid JSON matching this schema:

{
  "reasoning": "Brief 1-2 sentence internal reasoning",
  "request_classification": "DISCOVERY|QUESTION|BUG|IN_SCOPE_FEATURE|CHANGE_REQUEST",
  "support_check": "ACTIVE|EXPIRED|N_A",
  "scope_check": "INSIDE_SCOPE|OUTSIDE_SCOPE|N_A",
  "project_type": "todo_simple_crud|ecommerce_store|mobile_application|crm_system|erp_system|landing_page|admin_dashboard|mvp_web_app|corporate_website",
  "archetype_confidence": 0.95,
  "conflict_detected": false,
  "reconciliation_reason": "Reason if conflict was detected and scope changed",
  "intent": {
    "primary": "greeting|inquiry|price_request|objection|negotiation|scope_change_add|scope_change_mod|approval|rejection",
    "confidence": 0.95,
    "is_scope_addition": false,
    "is_modification": false
  },
  "requirements_analysis": {
    "is_complete": false,
    "completeness_score": 70,
    "missing_information": ["Feature detail 1"],
    "questions_needed_count": 1,
    "stop_asking_questions": false
  },
  "reply": "Conversational Arabic message responding directly to client",
  "next_best_action": "Suggested next best step",
  "action_proposals": {
    "invoice": {
      "propose": false,
      "requires_client_confirmation": true,
      "amount_usd": 0.0,
      "description": "Invoice description for approved scope"
    },
    "tasks": [],
    "stage_transition": "DISCOVERY|VALUATION|PROPOSAL|EXECUTION|COMPLETED"
  },
  "need_more_questions": ["Single specific question if needed"],
  "admin_work": {
    "summary": "Project summary for admin review",
    "developer_tasks": ["Task 1", "Task 2"],
    "admin_todos": ["Todo 1"],
    "developer_notes": "Architecture notes",
    "suggested_priorities": ["Phase 1: Setup", "Phase 2: Core Dev"]
  },
  "context_updates": {
    "current_stage": "GREETING|DISCOVERY|VALUATION|PROPOSAL|EXECUTION|COMPLETED",
    "current_goal": "Summary of current goal",
    "pending_features": ["Clean Feature 1"],
    "completed_features": ["Existing done feature"]
  }
}
PROMPT;

        $contextBuilder = new AiContextBuilder();
        $snapshot       = $contextBuilder->buildProjectSnapshot($project);
        $snapshotJson   = json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $userPrompt     = "FULL PROJECT SNAPSHOT:\n```json\n{$snapshotJson}\n```\n\nClient Latest Message: \"{$userText}\"";

        try {
            $openAiKey   = $adminSettings['openai_api_key'] ?? config('services.openai.key', '');
        } catch (\Throwable $e) {
            $openAiKey   = '';
        }
        $openAiModel = $adminSettings['openai_model'] ?? 'gpt-4o-mini';

        // 1. OpenAI Provider Attempt
        if (!empty($openAiKey)) {
            try {
                $response = Http::withoutVerifying()
                    ->timeout(25)
                    ->withToken($openAiKey)
                    ->post('https://api.openai.com/v1/chat/completions', [
                        'model'           => $openAiModel,
                        'messages'        => [
                            ['role' => 'system', 'content' => $systemPrompt],
                            ['role' => 'user',   'content' => $userPrompt],
                        ],
                        'response_format' => ['type' => 'json_object'],
                        'temperature'     => 0.2,
                    ]);

                if ($response->successful()) {
                    $rawText   = $response->json('choices.0.message.content') ?? '';
                    $validated = $this->validator->validateAndRepair($rawText);
                    if (!empty($validated['reply'])) {
                        return $validated;
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('[AiAgencyStructuredEngine] OpenAI API Exception, falling back to Gemini.', ['error' => $e->getMessage()]);
            }
        }

        // 2. Gemini Provider Attempt
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
                    $validated = $this->validator->validateAndRepair($rawText);
                    if (!empty($validated['reply'])) {
                        return $validated;
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('[AiAgencyStructuredEngine] Gemini API Exception, using single-brain fallback.', ['error' => $e->getMessage()]);
            }
        }

        // Single-Brain Intelligent Fallback Engine if API key is unconfigured or fails
        return $this->fallbackThinkingEngine($project, $userText, $context);
    }

    /**
     * Fallback decision engine that respects single-brain principles.
     */
    protected function fallbackThinkingEngine(Project $project, string $userText, array $context): array
    {
        $lower = mb_strtolower($userText);

        $isGreeting  = Str::contains($lower, ['سلام', 'مرحبا', 'ازيك', 'إزيك', 'أهلا', 'اهلا', 'hi', 'hello']);
        $hasIdea     = Str::contains($lower, ['اعمل', 'عايز', 'انشئ', 'مطلوب', 'ميزه', 'صفحة', 'تطبيق', 'موقع', 'زود', 'متجر', 'نظام', 'todo', 'app', 'system']);
        $isApproval  = Str::contains($lower, ['موافق', 'اعتمد', 'تمام', 'موافق على السعر', 'ابدأ', 'approve', 'accept', 'اصدر الفاتورة']);
        $isPriceReq  = Str::contains($lower, ['بكم', 'كم السعر', 'التكلفة', 'كام', 'أسعار', 'price', 'cost']);

        // Detect Archetype directly from user text
        $archetype = $this->pricingEngine->detectArchetype($userText);
        $previousType = $context['current_archetype'] ?? null;
        $conflictDetected = (!empty($previousType) && $previousType !== $archetype);

        // Feature title extraction (cleaned)
        $cleanFeature = $this->cleanFeatureTitle($userText);
        $pendingFeatures = $context['pending_features'] ?? [];
        if (!in_array($cleanFeature, $pendingFeatures, true) && !empty($cleanFeature)) {
            $pendingFeatures[] = $cleanFeature;
        }

        $valuation = $this->pricingEngine->calculateValuation($project, $pendingFeatures);

        if ($isApproval) {
            return $this->validator->applySchemaDefaults([
                'reasoning'            => 'العميل أعلن موافقته على التسعير والنطاق الفني للمكونات.',
                'project_type'         => 'component_based',
                'archetype_confidence' => 0.98,
                'conflict_detected'    => false,
                'intent' => [
                    'primary'    => 'approval',
                    'confidence' => 0.98,
                ],
                'requirements_analysis' => [
                    'is_complete'        => true,
                    'completeness_score' => 100,
                ],
                'reply' => "ممتاز جداً! تم تسجيل موافقتك على نطاق المشروع المكون وتكلفته التقديرية (تنفيذ خلال {$valuation['estimated_days']} يوم).\n\nهل ترغب في إصدار الفاتورة الرسمية وتأكيد الاتفاق لبدء تنفيذ المهام المباشرة؟\n\n[Card:ConfirmInvoice]",
                'next_best_action' => 'تأكيد العميل لإصدار الفاتورة وبدء التنفيذ.',
                'action_proposals' => [
                    'invoice' => [
                        'propose'                      => true,
                        'requires_client_confirmation' => true,
                        'amount_usd'                   => $valuation['recommended_usd'],
                        'description'                  => 'تطوير وتنفيذ نطاق المكونات لمشروع: ' . ($project->name ?? 'المشروع المعتمد'),
                    ],
                    'stage_transition' => 'PROPOSAL',
                ],
                'context_updates' => [
                    'current_stage'     => 'PROPOSAL',
                    'current_archetype' => 'component_based',
                    'pending_features'  => $pendingFeatures,
                ],
            ]);
        }

        if ($isGreeting && !$hasIdea && !$isPriceReq) {
            return $this->validator->applySchemaDefaults([
                'reasoning'            => 'تحية أولية من العميل دون تفاصيل فنية.',
                'project_type'         => 'component_based',
                'archetype_confidence' => 0.90,
                'intent' => [
                    'primary'    => 'greeting',
                    'confidence' => 0.95,
                ],
                'reply' => "وعليكم السلام ورحمة الله وبركاته! أهلاً بك. أنا مدير المشروع الذكي.\n\nيسعدني مساعدتك في تحليل وتطوير مشروعك. تفضل بشرح المكونات والخصائص التي ترغب في بنائها لنبدأ بدراسة المتطلبات سوياً.",
                'next_best_action' => 'الاستماع لفكرة العميل واستيضاح المكونات الأساسية.',
                'context_updates'  => ['current_stage' => 'GREETING'],
            ]);
        }

        $replyText = "تم تحليل طلبك وتفصيل التقدير الفني والمالي القائم على المكونات (Component-Based Valuation):\n\n";
        $replyText .= "📋 **نموذج التسعير**: {$valuation['type_name_ar']}\n";
        $replyText .= "💰 **إجمالي التكلفة التقديرية**: **{$valuation['recommended_usd']}$ USD** (نطاق: {$valuation['min_usd']}$ - {$valuation['max_usd']}$) ~ **{$valuation['converted_amount']} {$valuation['currency_symbol']}**\n";
        $replyText .= "⏱️ **إجمالي الوقت المتوقع**: حوالي **{$valuation['estimated_days']} أيام عمل** ({$valuation['total_hours']} ساعة تطويرية).\n\n";

        if (!empty($valuation['micro_components'])) {
            $replyText .= "🔍 **التفكيك الفني والمالي للمكونات (Components Breakdown)**:\n";
            foreach ($valuation['micro_components'] as $comp) {
                $replyText .= "  • **{$comp['name_ar']}**: ~{$comp['estimated_hours']} ساعة | **{$comp['cost_usd']}$ USD** ({$comp['converted_cost']} {$comp['currency_symbol']})\n";
            }
            $replyText .= "\n";
        }

        $replyText .= "هل مناسب لك هذا التفكيك المالي والفني لإصدار العقد والتأكيد؟";

        return $this->validator->applySchemaDefaults([
            'reasoning'            => "تم تقييم المتطلبات بحساب ساعات المكونات الفعلية والتكلفة التقديرية.",
            'project_type'         => 'component_based',
            'archetype_confidence' => 0.95,
            'conflict_detected'    => false,
            'reconciliation_reason'=> '',
            'intent' => [
                'primary'           => $isPriceReq ? 'price_request' : 'inquiry',
                'confidence'        => 0.90,
            ],
            'reply'            => $replyText,
            'next_best_action' => 'استكمال المتطلبات أو اعتماد التكلفة التقديرية للمكونات.',
            'context_updates'  => [
                'current_stage'     => 'VALUATION',
                'current_archetype' => 'component_based',
                'pending_features'  => $pendingFeatures,
            ],
        ]);
    }

    /**
     * Clean feature title from conversational filler words.
     */
    protected function cleanFeatureTitle(string $rawText): string
    {
        $text = strip_tags(trim($rawText));
        $fillers = [
            'بقولك عايز', 'بقولك عاوز', 'بقولك محتاج', 'بقولك انا عايز',
            'بقولك', 'عايز', 'عاوز', 'محتاج', 'مطلوب', 'ياريت', 'يا ريت',
            'انشئ', 'اعمل', 'زود', 'ضيف', 'نفسي في', 'i want', 'please add', 'need'
        ];

        foreach ($fillers as $filler) {
            if (mb_strpos(mb_strtolower($text), mb_strtolower($filler)) === 0) {
                $text = trim(mb_substr($text, mb_strlen($filler)));
            }
        }

        $cleaned = trim($text);
        return !empty($cleaned) ? mb_strimwidth($cleaned, 0, 50, '…') : mb_strimwidth($rawText, 0, 50, '…');
    }
}
