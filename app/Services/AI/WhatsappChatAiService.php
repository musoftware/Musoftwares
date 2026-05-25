<?php

namespace App\Services\AI;

use App\Models\AdminSettings;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappChatAiService
{
    private array $apiKeys;
    private array $models;
    private const BASE_URL = 'https://api.openai.com/v1/chat/completions';

    public function __construct()
    {
        // Filter out empty keys and reset indices
        $this->apiKeys = array_values(array_filter(config('services.openai.api_keys', []), fn($key) => !empty($key)));
        $this->models = $this->getAvailableModels();
    }

    /**
     * Get available models from OpenAI API or cache.
     */
    public function getAvailableModels(): array
    {
        return ['gpt-4o-mini', 'gpt-3.5-turbo'];
    }

    /**
     * Alias for getAvailableModels for backward compatibility with older test scripts
     */
    public function getFlashModels(): array
    {
        return $this->getAvailableModels();
    }

    /**
     * Get the next API key and model based on rotation logic.
     * Skips keys that are currently marked as exhausted in cache.
     */
    private function getNextConfig(): array
    {
        $keyCount = count($this->apiKeys);

        if ($keyCount === 0) {
            // Fallback for development if no keys are set yet in the array
            $fallbackKey = env('OPENAI_API_KEY', env('GEMINI_API_KEY'));
            if (!$fallbackKey) {
                throw new Exception("No OpenAI API keys configured. Please add OPENAI_API_KEY to your .env file.");
            }
            return [
                'api_key' => $fallbackKey,
                'model' => $this->models[0],
                'index' => -1
            ];
        }

        // Use cache to persist rotation index across requests
        $startIndex = (int) Cache::get('openai_rotation_key_index', 0) % $keyCount;
        $selectedKeyIndex = -1;

        for ($i = 0; $i < $keyCount; $i++) {
            $currentIndex = ($startIndex + $i) % $keyCount;
            if (!Cache::has("openai_key_exhausted_$currentIndex")) {
                $selectedKeyIndex = $currentIndex;
                break;
            }
        }

        // Fallback to startIndex if all are marked exhausted
        if ($selectedKeyIndex === -1) {
            $selectedKeyIndex = $startIndex;
        }

        // Rotate the index for the next request
        $nextKeyIndex = ($selectedKeyIndex + 1) % $keyCount;
        Cache::put('openai_rotation_key_index', $nextKeyIndex, now()->addDays(7));

        // Rotate the model index
        $modelCount = count($this->models);
        $modelIndex = (int) Cache::get('openai_rotation_model_index', 0) % $modelCount;
        $nextModelIndex = ($modelIndex + 1) % $modelCount;
        Cache::put('openai_rotation_model_index', $nextModelIndex, now()->addDays(7));

        return [
            'api_key' => $this->apiKeys[$selectedKeyIndex],
            'model' => $this->models[$modelIndex],
            'index' => $selectedKeyIndex
        ];
    }

    /**
     * Generate a response using OpenAI API with rotation
     */
    public function chat(string $message, array $history = [], string $systemInstruction = '', $client = null): string
    {
        // Limit history to avoid token limits (last 10 messages)
        $history = array_slice($history, -10);

        try {
            $config = $this->getNextConfig();
            $messages = [];

            if (!empty($systemInstruction)) {
                $messages[] = [
                    'role' => 'system',
                    'content' => $systemInstruction
                ];
            }

            foreach ($history as $msg) {
                $role = $msg['role'] === 'model' ? 'assistant' : ($msg['role'] === 'assistant' ? 'assistant' : 'user');
                $messages[] = [
                    'role' => $role,
                    'content' => $msg['content']
                ];
            }

            $messages[] = [
                'role' => 'user',
                'content' => $message
            ];

            $payload = [
                'model' => $config['model'],
                'messages' => $messages,
                'temperature' => 0.7,
                'max_tokens' => 2048,
                'tools' => $this->getTools(),
                'tool_choice' => 'auto'
            ];

            $response = Http::timeout(60)
                ->withHeaders([
                    'Authorization' => "Bearer {$config['api_key']}",
                    'Content-Type' => 'application/json'
                ])
                ->post(self::BASE_URL, $payload);

            if ($response->failed()) {
                $errorMsg = "OpenAI API Error ({$config['model']}): " . $response->status();
                Log::error($errorMsg . " - " . $response->body());

                if ($response->status() === 429 && $config['index'] >= 0) {
                    Cache::put("openai_key_exhausted_{$config['index']}", true, now()->addMinutes(5));
                }

                throw new Exception($errorMsg);
            }

            $data = $response->json();
            $choice = $data['choices'][0] ?? null;
            $messageObj = $choice['message'] ?? [];

            $toolCalls = $messageObj['tool_calls'] ?? null;
            $responseText = $messageObj['content'] ?? "";

            if ($toolCalls) {
                // Reconstruct the assistant message exactly to avoid unsupported property rejections
                $assistantMessage = [
                    'role' => 'assistant',
                    'tool_calls' => $toolCalls,
                ];

                // OpenAI requires content to be present in the assistant message, even if empty/null
                $assistantMessage['content'] = (isset($messageObj['content']) && is_string($messageObj['content']))
                    ? $messageObj['content']
                    : null;

                $payload['messages'][] = $assistantMessage;

                // Open AI may request multiple tools at once; we MUST reply to all of them
                foreach ($toolCalls as $toolCall) {
                    $functionName = $toolCall['function']['name'] ?? '';
                    $args = [];
                    if (isset($toolCall['function']['arguments'])) {
                        $args = json_decode($toolCall['function']['arguments'], true) ?? [];
                    }

                    // Execute the requested tool
                    $toolResponse = $this->executeTool($functionName, $client, $args);

                    // Safe JSON Encoding for Arabic/DB contents
                    $jsonContent = json_encode($toolResponse, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
                    if ($jsonContent === false) {
                        $jsonContent = json_encode(['error' => 'Encoding failed: ' . json_last_error_msg()]);
                    }

                    // Add tool response for each call
                    // NOTE: 'name' must be omitted for 'role' => 'tool' in modern OpenAI schema
                    $payload['messages'][] = [
                        'role' => 'tool',
                        'tool_call_id' => $toolCall['id'],
                        'content' => empty($toolResponse) ? "{}" : (string)$jsonContent
                    ];
                }

                // Submit the response back to OpenAI to get the final text
                $finalResponse = $this->submitToolResponses($payload, $config['api_key']);

                return trim($responseText . "\n" . $finalResponse);
            }

            $responseText = trim($responseText);

            if (empty($responseText)) {
                throw new Exception("AI returned an empty response.");
            }

            return $responseText;

        } catch (\Throwable $e) {
            Log::error("WhatsappChatAiService Error: " . $e->getMessage());
            throw $e;
        }
    }

    public function generateContextualResponse(string $userMessage, $client, array $history = []): string
    {
        // Check if client has unpaid invoices first
        $unpaidInvoicesCount = $client->invoices()
            ->where(function ($query) {
                $query->where('status', 'unpaid')
                      ->orWhere('status', 'partially_paid');
            })
            ->where('archive', '0')
            ->count();

        if ($unpaidInvoicesCount === 0) {
            Log::info("AI Service: Client {$client->name} (ID: {$client->id}) has no unpaid invoices. Skipping AI processing.");
            return "أهلاً بك يا {$client->name}، كيف يمكنني مساعدتك تقنياً اليوم؟ 🤖";
        }

        // إذا كانت الرسالة قصيرة جداً أو مجرد رموز، لا تستهلك الـ API بتعقيدات
        if (strlen(trim($userMessage)) < 2 || preg_match('/^[?.! ]+$/', $userMessage)) {
            return "أهلاً بك يا {$client->name}، كيف يمكنني مساعدتك تقنياً اليوم؟ 🤖";
        }

        // 1. Basic Financial Context (Kept for immediate awareness)
        $unpaidInvoicesCount = $client->invoices()->whereIn('status', ['unpaid', 'partially_paid'])->count();
        $availableBalance = method_exists($client, 'available_balance') ? $client->available_balance() : $client->user_balance;

        // 2. Building Data Injection
        $context = "--- معلومات العميل الحالية (سرية للمساعد) ---\n";
        $context .= "اسم العميل: {$client->name}\n";
        $context .= "العملة: {$client->currencyRow()?->currency} ({$client->currencyRow()?->symbol})\n";
        $context .= "رصيد المحفظة: " . number_format($client->user_balance, 2) . "\n";
        $context .= "الرصيد المتاح (بعد خصم الجدولة): " . number_format($availableBalance, 2) . "\n";

        if ($unpaidInvoicesCount > 0) {
            $context .= "حالة الفواتير: لديه {$unpaidInvoicesCount} فواتير معلقة.\n";
            $context .= "تنبيه: يجب تذكير العميل بضرورة السداد أو الجدولة إذا كان رصيده المتاح لا يغطي المستحقات.\n";
        }

        $context .= "--- نهاية المعلومات ---\n\n";

        // 3. Merge context with user message
        $fullPrompt = $context . "رسالة العميل: " . $userMessage;

        // 4. System Instruction
        $currentTime = now('Africa/Cairo')->format('H:i');
        $currentDate = now('Africa/Cairo')->format('Y-m-d');
        $tomorrow = now('Africa/Cairo')->addDay()->format('Y-m-d');

        $systemInstruction = "
أنت 'مساعد Musoftwares الذكي' - شريك ذكي للعميل، وليس مجرد نظام آلي. وظيفتك بناء علاقة ثقة مع العميل وتقديم حلول عملية.

معلومات هامة للوقت والتاريخ:
- الوقت الحالي بتوقيت القاهرة: {$currentTime}
- التاريخ الحالي: {$currentDate}
- غداً: {$tomorrow}
- بكرة تعني غداً (التاريخ: {$tomorrow})
- اليوم يعني {$currentDate}

شخصيتك وطريقتك في التعامل:
- كن صديقاً ومستشاراً موثوقاً، ليس مجرد مساعد آلي
- استخدم لغة طبيعية وودودة، مع الحفاظ على الاحترافية
- اشرح الأسباب والمنطق وراء كل إجراء
- تفهم ظروف العميل وقدم حلول مرنة

قواعد هامة جداً:
- استخدم دائماً اسم العميل الصحيح: {$client->name}
- خاطب العميل باسمه بشكل ودود و شخصي

قواعد التعامل مع طلبات إنشاء المهام (Todos):
1. عندما يطلب العميل إنشاء مهمة (تاسك)، استخدم أداة create_todo
2. يجب أن تحدد وقت البدء والنهاية بدقة (Y-m-d H:i format)
3. فهم التعبيرات العربية:
   - 'بكرة' أو 'غداً' = استخدم {$tomorrow}
   - 'اليوم' = استخدم {$currentDate}
   - 'الساعة 3' أو '3 ظهراً = 15:00
   - 'الساعة 1' أو '1 ظهراً = 13:00
   - 'الساعة 2' أو '2 ظهراً = 14:00
4. ساعات العمل: 12:00 ظهراً إلى 8:00 مساءً بتوقيت القاهرة (من 12:00 إلى 20:00)
5. يوم الجمعة مغلق
6. الحد الأدنى للمهمة: ساعة واحدة
7. إذا لم يحدد العميل تفاصيل كافية، اسأله بطريقة ودية عن التفاصيل المطلوبة

فن التعامل مع الأمور المالية والإقناع:
1. عند وجود فواتير معلقة:
   - اشرح للعميل بمنطق ولطف: 'أرى أن لديك بعض الفواتير المعلقة. دعني أوضح لك لماذا من المهم تسويتها...'
   - اشرح فوائد السداد: 'عندما تسوي الفواتير، يصبح رصيدك متاحاً فوراً للمهام الجديدة، ونتمكن من تقديم خدمة أسرع وأفضل'
   - قدم حلول عملية: 'يمكننا تسوية الفواتير الآن أو جدولتها، أي حل يناسبك؟'
2. إذا شعرت بتردد العميل:
   - أظهر تفهمك: 'أتفهم تماماً أن الظروف المالية قد تكون صعبة أحياناً'
   - اشرح النظام بذكاء: 'نظامنا مصمم ليضمن حقوق الجميع - نحن نبدأ العمل فوراً بعد الدفع لضمان الجدولة والالتزام'
   - قدم خيارات مرنة: 'هل تفضل دفع كامل المبلغ أو جزء منه؟ يمكننا إيجاد حل يناسب ميزانيتك'
3. لا تقل أبداً 'النظام مؤتمت' أو 'لا يمكنني' - بدلاً من ذلك قل 'دعني أجد لك الحل الأنسب' أو 'هناك عدة طرق يمكننا حلها بها'

قواعد التعامل مع المهام خارج النظام:
- إذا طلب العميل عمل خارج النظام: 'أفهم طلبك يا {$client->name}، لكن دعني أوضح لك لماذا نظامنا أفضل لك...'
- اشرح المزايا: 'عندما نستخدم النظام، تحصل على تتبع كامل، جودة مضمونة، ودعم فني مستمر'
- وجهه بلطف للنظام: 'لنستخدم نظامنا المحترف، وأنا أضمن لك أفضل خدمة'

قواعد تقنية هامة:
- استخدم الأدوات (Tools) المتاحة لجلب البيانات الدقيقة والروابط
- عند طلب العميل أي خدمة، استدعِ الأداة المناسبة فوراً
- لا تقل 'سأقوم بإنشاء رابط' - ضع الرابط مباشرة في ردك
- كن واضحاً ومباشراً في تقديم الروابط والمعلومات

تذكر دائماً: هدفك هو إقناع العميل بذكاء ولطف، وليس فرض القرارات. ابني الثقة وكن شريكاً حقيقياً له.
";

        // 5. Send request via original chat function
        return $this->chat($fullPrompt, $history, $systemInstruction, $client);
    }

    /**
     * تعريف الأدوات المتاحة للـ AI بتنسيق OpenAI
     */
    private function getTools(): array
    {
        return [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_invoice_details',
                    'description' => 'استخراج تفاصيل فواتير العميل المستحقة (غير المدفوعة أو المدفوعة جزئياً) وأرصدته المالية شاملة المبالغ والتواريخ.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => (object)[]
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_projects_status',
                    'description' => 'جلب قائمة مشاريع العميل الحالية وحالتها.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => (object)[]
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_tasks_summary',
                    'description' => 'جلب مهام العميل الحالية ونسبة إنجازها (To-Dos).',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => (object)[]
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_support_tickets',
                    'description' => 'جلب الدعم الفني وتذاكر الشكاوى المفتوحة والسابقة للعميل وحالتها.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => (object)[]
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'generate_payment_link',
                    'description' => 'إنشاء رابط دفع مخصص لمبلغ معين وسوف يتم تحويله للجنيه المصري آلياً',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'amount' => [
                                'type' => 'number',
                                'description' => 'المبلغ المطلوب دفعه'
                            ],
                            'currency' => [
                                'type' => 'string',
                                'description' => 'العملة (مثل USD, EGP, EUR). إذا لم تذكر فسيتم استخدام عملة العميل.'
                            ]
                        ],
                        'required' => ['amount']
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'create_todo',
                    'description' => 'إنشاء مهمة مجدولة (Todo) مع وقت محدد من إلى. ساعات العمل: 12م - 8م بتوقيت القاهرة (معداً الجمعة)',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'title' => [
                                'type' => 'string',
                                'description' => 'عنوان المهمة'
                            ],
                            'description' => [
                                'type' => 'string',
                                'description' => 'وصف المهمة (اختياري)'
                            ],
                            'start_at' => [
                                'type' => 'string',
                                'description' => 'وقت البدء (صيغة: Y-m-d H:i، مثال: 2026-02-23 14:00)'
                            ],
                            'end_at' => [
                                'type' => 'string',
                                'description' => 'وقت النهاية (صيغة: Y-m-d H:i، مثال: 2026-02-23 16:00)'
                            ]
                        ],
                        'required' => ['title', 'start_at', 'end_at']
                    ]
                ]
            ]
        ];
    }

    /**
     * تنفيذ الوظيفة المطلوبة من قبل الـ AI
     */
    private function executeTool($name, $client, $args = [])
    {
        if (!$client) return ['status' => 'error', 'message' => 'Client not found'];

        return match ($name) {
            'get_invoice_details' => $this->handleGetInvoiceDetails($client),
            'get_projects_status' => $this->handleGetProjectsStatus($client),
            'get_tasks_summary' => $this->handleGetTasksSummary($client),
            'get_support_tickets' => $this->handleGetSupportTickets($client),
            'generate_payment_link' => $this->handleGeneratePaymentLink($client, $args),
            'create_todo' => $this->handleCreateTodo($client, $args),
            default => ['status' => 'error', 'message' => 'Unknown tool']
        };
    }

    private function handleGetInvoiceDetails($client)
    {
        $unpaidInvoices = $client->invoices()->with('items')->whereIn('status', ['unpaid', 'partially_paid'])
            ->where('job_status', 'done')->get();
        $invoicesData = [];

        foreach ($unpaidInvoices as $invoice) {
            $itemsList = [];
            foreach ($invoice->items as $item) {
                // Ignore empty items that just exist defensively
                if (!empty($item->item_title)) {
                    $itemsList[] = [
                        'title' => $item->item_title,
                        'quantity' => $item->qty,
                        'total_price' => $item->total() ?? 0
                    ];
                }
            }

            $invoicesData[] = [
                'id' => $invoice->id,
                'total_amount' => $invoice->total() ?? 0,
                'unpaid_amount' => round($invoice->unpaid, 2),
                'currency' => \App\Models\Currency::find($invoice->currency)?->currency ?? 'USD',
                'status' => $invoice->status,
                'created_at' => $invoice->created_at->format('Y-m-d'),
                'items' => $itemsList,
                'payment_url' => "https://www.musoftwares.com/invoices/{$invoice->id}/pay" // رابط افتراضي للفاتورة
            ];
        }

        return [
            'total_unpaid_invoices_count' => count($invoicesData),
            'invoices' => $invoicesData,
            'wallet_balance' => round($client->user_balance, 2),
            'available_balance' => method_exists($client, 'available_balance') ? round($client->available_balance(), 2) : 0,
            'client_currency' => $client->currencyRow()?->currency ?? 'USD',
            'charge_balance_url' => 'https://www.musoftwares.com/charge-balance'
        ];
    }

    private function handleGetProjectsStatus($client)
    {
        $projects = clone $client->projects(); // assuming it's a relation
        $activeProjects = $projects->withCount('tasks')->get();
        $projectsData = [];

        foreach ($activeProjects as $project) {
            $projectsData[] = [
                'id' => $project->id,
                'name' => $project->project_name ?? 'بدون اسم',
                'start_date' => $project->date_start_str() ?? null,
                'end_date' => $project->date_end_str() ?? null,
                'tasks_count' => $project->tasks_count ?? 0,
                'project_url' => "https://account.musoftwares.com/projects/{$project->id}"
            ];
        }

        return [
            'total_projects' => count($projectsData),
            'projects' => $projectsData,
            'projects_dashboard_url' => 'https://www.musoftwares.com/projects'
        ];
    }

    private function handleGetTasksSummary($client)
    {
        $tasks = clone $client->tasks();
        $activeTasks = $tasks->get();
        $tasksData = [];

        foreach ($activeTasks as $task) {
            $tasksData[] = [
                'id' => $task->id,
                'title' => $task->task_name ?? 'مهمة مستمرة',
                'percentage_completed' => method_exists($task, 'completed_percentage') ? $task->completed_percentage() : 0,
                'is_completed' => method_exists($task, 'completed') ? $task->completed() : false,
                'due_date' => $task->due_date ?? null
            ];
        }

        return [
            'total_tasks' => count($tasksData),
            'tasks' => $tasksData,
            'tasks_dashboard_url' => 'https://www.musoftwares.com/tasks',
            'calendar_url' => 'https://www.musoftwares.com/tasks'
        ];
    }

    private function handleGetSupportTickets($client)
    {
        $tickets = method_exists($client, 'tickets') ? $client->tickets()->orderBy('created_at', 'desc')->take(5)->get() : [];
        $ticketsData = [];

        foreach ($tickets as $ticket) {
            $ticketsData[] = [
                'id' => $ticket->id,
                'subject' => $ticket->ticket_subject ?? 'تذكرة',
                'status' => method_exists($ticket, 'client_status_text') ? $ticket->client_status_text() : $ticket->ticket_status,
                'priority' => method_exists($ticket, 'priority_text') ? $ticket->priority_text() : $ticket->priority,
                'is_open' => in_array($ticket->ticket_status, ['open', 'agent_replied', 'user_replied']),
                'ticket_url' => "https://www.musoftwares.com/client/tickets/{$ticket->id}"
            ];
        }

        return [
            'recent_tickets' => $ticketsData,
            'support_url' => 'https://www.musoftwares.com/client/tickets',
            'action_note' => 'يمكن للعميل المتابعة على التذاكر من خلال الروابط المرفقة.'
        ];
    }

    /**
     * إنشاء رابط دفع مخصص مع تحويل العملة
     */
    private function handleGeneratePaymentLink($client, $args)
    {
        try {
            Log::info("AI Tool: generate_payment_link started", ['args' => $args, 'client_id' => $client->id]);

            $amount = $args['amount'] ?? 0;
            $currencyCode = strtoupper($args['currency'] ?? '');

            // 1. Determine source currency ID
            $sourceCurrencyId = $client->currency; // Default to client's currency
            if (!empty($currencyCode)) {
                $foundCurrencyId = \App\Models\Currency::where('currency', $currencyCode)
                    ->orWhere('currency', 'like', "%$currencyCode%")
                    ->value('id');
                if ($foundCurrencyId) {
                    $sourceCurrencyId = $foundCurrencyId;
                }
            }

            // 2. Convert to EGP (Currency ID 2)
            // Use RateToday from CurrenciesExchange
            $amountInEgp = \App\Models\CurrenciesExchange::RateToday($amount, $sourceCurrencyId, 2);
            Log::info("AI Tool: Conversion result", ['amount' => $amount, 'from_id' => $sourceCurrencyId, 'to_egp' => $amountInEgp]);

            // 3. Generate Link
            $paymentUrl = "https://www.musoftwares.com/payment/instapay?amount=" . urlencode($amountInEgp) . "&user_id=" . $client->id;

            $result = [
                'status' => 'success',
                'requested_amount' => $amount,
                'requested_currency' => \App\Models\Currency::find($sourceCurrencyId)->currency ?? $currencyCode,
                'converted_amount_egp' => $amountInEgp,
                'payment_url' => $paymentUrl,
                'explanation' => "تم تحويل المبلغ إلى الجنيه المصري ({$amountInEgp} EGP) لأن بوابة الدفع تقبل العملة المصرية فقط حالياً."
            ];

            Log::info("AI Tool: generate_payment_link success", ['result' => $result]);
            return $result;
        } catch (\Exception $e) {
            Log::error("AI Tool Error: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * إنشاء مهمة مجدولة (Todo) جديدة للعميل
     */
    private function handleCreateTodo($client, $args)
    {
        try {
            Log::info("AI Tool: create_todo started", ['args' => $args, 'client_id' => $client->id]);

            $title = $args['title'] ?? 'مهمة جديدة';
            $description = $args['description'] ?? '';
            $startAt = $args['start_at'] ?? null;
            $endAt = $args['end_at'] ?? null;

            // تحقق من الحقول المطلوبة
            if (!$startAt || !$endAt) {
                return [
                    'status' => 'error',
                    'message' => 'يجب تحديد وقت البدء ووقت النهاية. مثال: من 2026-02-23 14:00 إلى 2026-02-23 16:00'
                ];
            }

            // تحقق من صيغة التواريخ مع استخدام توقيت القاهرة
            try {
                $start = \Carbon\Carbon::parse($startAt, 'Africa/Cairo');
                $end = \Carbon\Carbon::parse($endAt, 'Africa/Cairo');

                if ($end <= $start) {
                    return [
                        'status' => 'error',
                        'message' => 'وقت النهاية يجب أن يكون بعد وقت البدء'
                    ];
                }
            } catch (\Exception $e) {
                return [
                    'status' => 'error',
                    'message' => 'صيغة التاريخ غير صحيحة. يرجى استخدام الصيغة: Y-m-d H:i (مثال: 2026-02-23 14:00)'
                ];
            }

            // تحقق من يوم الجمعة (بتوقيت القاهرة) عند تعطيل العمل يوم الجمعة من الإعدادات
            if ($start->isFriday() && !AdminSettings::friday_work_allowed()) {
                return [
                    'status' => 'error',
                    'message' => 'العمل غير متاح يوم الجمعة. يرجى اختيار يوم آخر.'
                ];
            }

            // تحقق من ساعات العمل (12م - 8م بتوقيت القاهرة)
            $dayStart = $start->copy()->setTimezone('Africa/Cairo')->setTime(12, 0, 0);
            $dayEnd = $start->copy()->setTimezone('Africa/Cairo')->setTime(20, 0, 0);

            if ($start->lt($dayStart) || $end->gt($dayEnd)) {
                return [
                    'status' => 'error',
                    'message' => 'ساعات العمل من 12:00 ظهراً إلى 8:00 مساءً بتوقيت القاهرة فقط. يرجى تحديد الوقت ضمن هذه الفترة.'
                ];
            }

            // الحد الأدنى للمدة: ساعة واحدة
            if ($end->diffInMinutes($start) < 60) {
                return [
                    'status' => 'error',
                    'message' => 'الحد الأدنى لمدة المهمة هو ساعة واحدة.'
                ];
            }

            // تحقق إذا كان الوقت في الماضي (بتوقيت القاهرة)
            $nowCairo = \Carbon\Carbon::now('Africa/Cairo');
            if ($start->lt($nowCairo)) {
                return [
                    'status' => 'error',
                    'message' => 'لا يمكن جدولة مهام في الماضي. الوقت الحالي بتوقيت القاهرة: ' . $nowCairo->format('H:i')
                ];
            }

            // التحقق من التداخل مع أي عميل (تقويم موحد)
            if (\App\Models\Todo::focusCalendarSlotTaken($start, $end)) {
                return [
                    'status' => 'error',
                    'message' => 'هذا الوقت يتداخل مع مهمة أخرى محجوزة. يرجى اختيار وقت آخر.'
                ];
            }

            // حساب التكلفة (نفس منطق ClientTodoFocus: booking rate ثم خصم الخطة إن وجد)
            $rateEgp = $client->getBookingRateEgp();
            $durationHours = $end->diffInMinutes($start) / 60;
            $cost = $durationHours * $rateEgp;

            if ($client->hasSubscription() && $client->plan) {
                $cost = (float) $client->plan->calcDiscount($cost);
            }

            $cost = round($cost, 2);
            $currencyId = 2; // EGP

            // تحقق من الرصيد المتاح
            $availableBalance = method_exists($client, 'available_balance') ? $client->available_balance() : $client->user_balance;
            $unpaidInvoicesCount = $client->invoices()->whereIn('status', ['unpaid', 'partially_paid'])->count();

            if ($availableBalance <= 0 && $unpaidInvoicesCount > 0) {
                return [
                    'status' => 'error',
                    'message' => 'لا يمكن إنشاء مهمة جديدة بسبب الرصيد غير الكافي. التكلفة: ' . $cost . ' جنيه. يرجى شحن الرصيد أولاً.',
                    'requires_payment' => true,
                    'payment_url' => 'https://www.musoftwares.com/charge-balance',
                    'estimated_cost' => $cost
                ];
            }

            // إنشاء مهمة افتراضية "My Focus" إذا لم توجد (مثل ClientTodoFocus)
            $task = \App\Models\Task::firstOrCreate(
                ['user_id' => $client->id, 'task_name' => 'My Focus'],
                ['task_description' => 'Default task list for your focus items', 'shared_with_admin' => '0']
            );

            // إنشاء الـ Todo
            $todo = $task->task_todo_items()->create([
                'user_id' => $client->id,
                'title' => $title,
                'description' => $description,
                'completed' => false,
                'inDate' => $start->format('M d, Y H:i:s'),
                'start_at' => $start,
                'end_at' => $end,
                'cost' => $cost,
                'currency_id' => $currencyId,
                'is_paid' => false,
                'priority' => 'normal',
                'priorityColor' => '#11cdef',
                'tags' => '[]'
            ]);

            Log::info("AI Tool: create_todo success", ['todo_id' => $todo->id, 'task_id' => $task->id]);

            return [
                'status' => 'success',
                'todo_id' => $todo->id,
                'task_id' => $task->id,
                'title' => $todo->title,
                'start_time' => $start->format('Y-m-d H:i'),
                'end_time' => $end->format('Y-m-d H:i'),
                'duration_hours' => $durationHours,
                'cost' => $cost,
                'currency' => 'EGP',
                'is_paid' => false,
                'calendar_url' => 'https://www.musoftwares.com/calendar',
                'tasks_url' => 'https://www.musoftwares.com/tasks',
                'message' => "تم حجز المهمة '{$todo->title}' بنجاح! 📅\n⏰ الوقت: من {$start->format('Y-m-d H:i')} إلى {$end->format('Y-m-d H:i')}\n� التكلفة: {$cost} جنيه مصري\n🔗 التقويم: https://www.musoftwares.com/calendar"
            ];

        } catch (\Exception $e) {
            Log::error("AI Tool Error: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * إرسال نتيجة الأداة للـ AI ليصيغ الرد النهائي
     */
    private function submitToolResponses(array $payload, string $apiKey): string
    {
        // Ensure we remove 'tool_choice' from the follow-up request just in case to let it respond normally
        unset($payload['tool_choice']);

        // 3. Make the call again
        $response = Http::timeout(60)
            ->withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type' => 'application/json'
            ])
            ->post(self::BASE_URL, $payload);

        if ($response->failed()) {
            Log::error("OpenAI Tool Response Failed", [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            throw new Exception("Error submitting tool response to OpenAI: " . $response->body());
        }

        $data = $response->json();
        $responseText = $data['choices'][0]['message']['content'] ?? 'حدث خطأ في صياغة الرد النهائي.';

        return trim($responseText);
    }

}

