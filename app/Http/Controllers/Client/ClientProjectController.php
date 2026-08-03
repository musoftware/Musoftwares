<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Resources\ClientProjectResource;
use App\Jobs\AiProcessMessageJob;
use App\Models\Project;
use App\Models\ProjectComment;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ClientProjectController extends Controller
{
    public function index(Request $request)
    {
        $projects = Project::where('user_id', $request->user()->id)
            ->where('archived', 0)
            ->withCount(['tasks', 'publishedReports', 'files'])
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Client/Projects/Index', [
            'projects' => fn () => ClientProjectResource::collection($projects),
        ]);
    }

    public function show(Request $request, Project $project)
    {
        $this->authorize('view', $project);

        $project->loadCount(['tasks', 'publishedReports', 'files']);

        $team = $this->resolveTeam($project);

        // Always load discussions for the AI workspace (no tabs)
        $discussions = $this->loadDiscussions($project);

        // Support tickets
        $supportTickets = \App\Models\Ticket::where('user_id', $request->user()->id)
            ->latest()
            ->limit(5)
            ->get(['id', 'ticket_subject as subject', 'ticket_status as status', 'created_at']);

        return Inertia::render('Client/Projects/Show', [
            'project'        => fn () => (new ClientProjectResource($project))->resolve(),
            'team'           => fn () => $team,
            'discussions'    => fn () => $discussions,
            'supportTickets' => fn () => $supportTickets,
            'aiContext'      => fn () => $project->ai_context,
            'aiStage'        => fn () => $project->ai_context['current_stage'] ?? 'greeting',
            'aiQuestions'   => fn () => collect($project->ai_questions ?? [])->where('answered', false)->values(),
            'aiActionsLog'  => fn () => array_slice($project->ai_actions_log ?? [], 0, 10),
        ]);
    }

    /**
     * Best-effort project team loader.
     */
    protected function resolveTeam(Project $project): array
    {
        $members = [];

        if (method_exists($project, 'team')) {
            try {
                $members = $project->team()->get()->map(fn ($u) => [
                    'id'         => (int) $u->id,
                    'name'       => $u->name ?? '',
                    'avatar_url' => $u->avatar_url ?? null,
                    'role'       => $u->pivot->role ?? null,
                ])->all();
            } catch (\Throwable $e) {
                $members = [];
            }
        }

        if (empty($members) && method_exists($project, 'user')) {
            $owner = $project->user ?? $project->client ?? null;
            if ($owner) {
                $members = [[
                    'id'         => (int) $owner->id,
                    'name'       => $owner->name ?? '',
                    'avatar_url' => $owner->avatar_url ?? null,
                    'role'       => 'Owner',
                ]];
            }
        }

        return $members;
    }

    /**
     * Load all discussion messages for the project chat.
     */
    protected function loadDiscussions(Project $project): array
    {
        // Project-level comments (the main AI workspace chat)
        $projectComments = ProjectComment::where('project_id', $project->id)
            ->where('commentable_type', Project::class)
            ->with('author:id,name')
            ->oldest()
            ->limit(200)
            ->get()
            ->map(fn ($c) => [
                'id'             => $c->id,
                'body'           => $c->body,
                'author_id'      => $c->author_id,
                'guest_name'     => $c->guest_name,
                'created_at'     => $c->created_at,
                'parent_id'      => $c->parent_id,
                'commentable_id' => $c->commentable_id,
                'type'           => 'project',
                'author'         => $c->author ? ['id' => $c->author->id, 'name' => $c->author->name] : null,
                'file'           => null,
            ]);

        return $projectComments->toArray();
    }

    public function create()
    {
        return Inertia::render('Client/Projects/Create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'project_name' => 'required|string|max:255',
            'description'  => 'nullable|string',
        ]);

        $project = Project::create([
            'user_id'      => $request->user()->id,
            'project_name' => $data['project_name'],
            'status'       => 'open',
            'archived'     => false,
            'percentage'   => 0,
            'budget'       => 0,
            'total_paid'   => 0,
            'date_start'   => now(),
            'ai_enabled'   => false,
            'ai_summary'   => [
                'project_type' => null,
                'features'     => [],
                'current_goal' => null,
                'missing_info' => ['Budget', 'Timeline', 'Target audience'],
                'complexity'   => null,
            ],
            'ai_questions' => [
                ['id' => Str::uuid(), 'question' => 'What is the main purpose of this project?', 'answered' => false],
                ['id' => Str::uuid(), 'question' => 'Who is the target audience?', 'answered' => false],
                ['id' => Str::uuid(), 'question' => 'What is your estimated budget?', 'answered' => false],
            ],
            'ai_actions_log'       => [],
            'ai_understanding_pct' => 0,
        ]);

        // Seed initial AI greeting
        ProjectComment::create([
            'project_id'       => $project->id,
            'author_id'        => null,
            'guest_name'       => 'AI',
            'body'             => '[System: Welcome! Your project has been created. Start by describing what you need — type anything and I\'ll start organizing your project automatically.]',
            'commentable_type' => Project::class,
            'commentable_id'   => $project->id,
        ]);

        if (! empty($data['description'])) {
            // Save description as first client message
            $comment = ProjectComment::create([
                'project_id'       => $project->id,
                'author_id'        => $request->user()->id,
                'body'             => $data['description'],
                'commentable_type' => Project::class,
                'commentable_id'   => $project->id,
            ]);
        }

        return redirect()->route('client.projects.show', $project->id);
    }

    /**
     * Store a new chat message (text + optional file attachment).
     * Dispatches AI processing job.
     */
    public function storeMessage(Request $request, Project $project)
    {
        $this->authorize('view', $project);

        $data = $request->validate([
            'body' => 'nullable|string|max:5000',
            'file' => 'nullable|file|max:20480', // 20MB max
        ]);

        if (empty($data['body']) && !$request->hasFile('file')) {
            return response()->json(['ok' => false, 'message' => 'Message or file required.'], 422);
        }

        $fileData = null;
        if ($request->hasFile('file')) {
            $file    = $request->file('file');
            $stored  = $file->store('project-chat-files/' . $project->id, 'public');
            $fileData = [
                'path'          => $stored,
                'original_name' => $file->getClientOriginalName(),
                'mime'          => $file->getMimeType(),
                'size'          => $file->getSize(),
                'url'           => asset('storage/' . $stored),
            ];
        }

        $body = $data['body'] ?? ($fileData ? '📎 ' . $fileData['original_name'] : '');

        $comment = ProjectComment::create([
            'project_id'       => $project->id,
            'author_id'        => $request->user()->id,
            'body'             => $body,
            'commentable_type' => Project::class,
            'commentable_id'   => $project->id,
        ]);

        // If file was uploaded, store metadata in body as JSON marker
        if ($fileData) {
            $comment->update(['body' => '[File:' . json_encode($fileData) . ']' . ($data['body'] ? "\n" . $data['body'] : '')]);
        }

        // Process AI tools & Orchestrator
        $aiResult = ['billed_amount' => '0.00', 'currency_symbol' => 'EGP'];
        if (!$project->ai_enabled) {
            $project->update(['ai_enabled' => true]);
        }
        $orchestrator = new \App\Services\AI\AiProjectOrchestratorService(new \App\Services\AI\AiToolRegistry());
        $aiResult = $orchestrator->processClientMessage($project, $body, $request->user()->id);

        // Re-load all discussions so both client message & AI reply are returned
        $discussions = $this->loadDiscussions($project);

        return response()->json([
            'ok'              => true,
            'billed_amount'   => $aiResult['billed_amount'] ?? '0.00',
            'currency_symbol' => $aiResult['currency_symbol'] ?? 'EGP',
            'discussions'     => $discussions,
        ]);
    }

    /**
     * Approve AI Valuation and start project execution (generates tasks for developer).
     */
    public function approveBudget(Request $request, Project $project)
    {
        $this->authorize('view', $project);

        $project->update([
            'status' => 'open',
        ]);

        $project->updateAiContext([
            'current_stage' => 'EXECUTION',
        ]);

        // 1. Generate Developer Tasks & Execution Plan
        $todoTool = (new \App\Services\AI\AiToolRegistry())->getTool('create_todos');
        if ($todoTool) {
            $features = $project->ai_context['pending_features'] ?? $project->ai_summary['features'] ?? ['Core Platform Requirements'];
            $todos = [];
            foreach ($features as $feat) {
                $todos[] = [
                    'title'       => 'تنفيذ: ' . mb_strimwidth($feat, 0, 50, '…'),
                    'description' => 'مهمة معتمدة من العميل بعد فتح الاعتماد ودفع الدفعة الأولى (50%)',
                    'priority'    => 'high',
                ];
            }
            $todoTool->execute($project, ['todos' => $todos]);
        }

        // 2. Schedule tasks into Admin Working Hours & Queue 15-min FCM + Email notifications
        $scheduler = new \App\Services\AI\TaskSchedulerService();
        $scheduleResult = $scheduler->scheduleProjectTasks($project);

        // 3. Post confirmation system message
        ProjectComment::create([
            'project_id'       => $project->id,
            'author_id'        => null,
            'guest_name'       => 'AI Agency Manager',
            'body'             => '[System: تم سداد/اعتماد الدفعة الأولى (50%) بنجاح! تم اعتماد الميزانية وجدولة المهام تلقائياً في أوقات العمل الرسمية للمبرمج، وتم تفعيل إشعارات الـ FCM والإيميل للأدمن قبل كل مهمة بـ 15 دقيقة.]',
            'commentable_type' => Project::class,
            'commentable_id'   => $project->id,
        ]);

        return response()->json([
            'ok'              => true,
            'message'         => 'تم اعتماد الميزانية وجدولة المهام بنجاح!',
            'schedule_result' => $scheduleResult,
        ]);
    }

    /**
     * Explicit Client Confirmation for Invoice Proposal & Task Generation.
     */
    public function confirmInvoice(Request $request, Project $project)
    {
        $this->authorize('view', $project);

        $context = $project->ai_context ?? [];
        $features = $context['pending_features'] ?? [];

        $pricingEngine = new \App\Services\AI\ScopePricingEngine();
        $valuation     = $pricingEngine->calculateValuation($project, $features);

        $executor = new \App\Services\AI\AiAgencyLaravelExecutor();
        
        // 1. Create Invoice safely
        $invoice = $executor->executeApprovedInvoice(
            $project,
            (float) ($valuation['recommended_usd'] ?? 450.0),
            'تطوير المتطلبات المعتمدة للمشروع: ' . $project->project_name,
            $request->user()->id
        );

        // 2. Generate Developer Tasks
        $tasksToCreate = [];
        foreach ($features as $f) {
            $tasksToCreate[] = [
                'title'       => 'تنفيذ: ' . mb_strimwidth($f, 0, 50, '…'),
                'description' => 'مهمة مضافة تلقائياً بعد اعتماد العميل للفاتورة وتأكيد الاتفاق.',
                'priority'    => 'high',
            ];
        }
        if (empty($tasksToCreate)) {
            $tasksToCreate[] = [
                'title'       => 'تنفيذ المتطلبات المعتمدة للمشروع',
                'description' => 'مهمة مضافة تلقائياً بعد إذن إصدار الفاتورة.',
                'priority'    => 'high',
            ];
        }
        $executor->executeApprovedTasks($project, $tasksToCreate);

        // 3. Post system comment confirming invoice creation
        $currencyCode = $invoice->currency?->currency ?? 'EGP';
        ProjectComment::create([
            'project_id'       => $project->id,
            'author_id'        => null,
            'guest_name'       => 'AI Project Manager',
            'body'             => "🎉 **تم إذن إصدار الفاتورة بنجاح!**\n\nتم إصدار الفاتورة رقم `#{$invoice->id}` بقيمة **{$invoice->total} {$currencyCode}**، وتم توليد مهام التطوير التنفيذية ونقل المشروع إلى مرحلة التنفيذ المباشر.",
            'commentable_type' => Project::class,
            'commentable_id'   => $project->id,
        ]);

        $discussions = $this->loadDiscussions($project);

        return response()->json([
            'ok'          => true,
            'message'     => 'تم إصدار الفاتورة وتوليد المهام بنجاح!',
            'invoice_id'  => $invoice->id,
            'discussions' => $discussions,
        ]);
    }


    /**
     * Dismiss (mark as answered) an AI question.
     */
    public function dismissAiQuestion(Request $request, Project $project, string $questionId)
    {
        $this->authorize('view', $project);

        $questions = collect($project->ai_questions ?? [])
            ->map(function ($q) use ($questionId) {
                if ((string)($q['id'] ?? '') === $questionId) {
                    $q['answered'] = true;
                }
                return $q;
            })->values()->toArray();

        $project->update(['ai_questions' => $questions]);

        return response()->json(['ok' => true]);
    }

    public function activateAi(Request $request, Project $project)
    {
        $this->authorize('view', $project);

        $user = $request->user();

        $egpCurrency = \App\Models\Currency::where('currency', 'EGP')->first();
        if (!$egpCurrency) {
            return response()->json([
                'ok'      => false,
                'message' => 'EGP currency configuration not found.',
            ], 422);
        }

        $costInUserCurrency = \App\Models\CurrenciesExchange::RateToday(10.0, $egpCurrency->id, $user->currency_id);

        if ((float) $user->user_balance < $costInUserCurrency) {
            return response()->json([
                'ok'          => false,
                'insufficient' => true,
                'balance'     => (float) $user->user_balance,
                'required'    => $costInUserCurrency,
                'message'     => 'Insufficient wallet balance. You need ' . number_format($costInUserCurrency, 2) . ' ' . $user->currency_name() . ' (10 EGP) to activate the AI.',
            ], 422);
        }

        \DB::transaction(function () use ($user, $project, $costInUserCurrency) {
            \App\Models\Transaction::create([
                'user_id'     => $user->id,
                'amount'      => -$costInUserCurrency,
                'reason'      => 'AI Project Manager Activation for project: ' . $project->project_name,
                'category'    => 'other',
                'type'        => 'used',
                'project_id'  => $project->id,
                'currency_id' => $user->currency_id,
            ]);

            $project->update([
                'ai_enabled'           => true,
                'last_ai_charged_at'   => \Carbon\Carbon::now('Africa/Cairo'),
                'ai_understanding_pct' => 5, // Seed initial understanding
            ]);

            \App\Helpers\BalancesHelper::UpdateBalance($user, $project);
        });

        // Post AI activation system message
        ProjectComment::create([
            'project_id'       => $project->id,
            'author_id'        => null,
            'guest_name'       => 'AI',
            'body'             => '[System: AI Project Manager activated! I\'m now analyzing your project. Start describing what you need and I\'ll automatically organize everything — requirements, tasks, timelines, and more.]',
            'commentable_type' => Project::class,
            'commentable_id'   => $project->id,
        ]);

        return response()->json([
            'ok'      => true,
            'message' => 'AI Project Manager activated successfully!',
        ]);
    }
}
