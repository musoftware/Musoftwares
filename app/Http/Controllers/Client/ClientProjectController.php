<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Resources\ClientProjectResource;
use App\Models\Project;
use Illuminate\Http\Request;
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

        $recentReports = $project->publishedReports()
            ->latest('published_at')
            ->limit(5)
            ->get(['id', 'title', 'published_at']);

        $allowedTabs = ['tasks', 'discussions', 'files', 'financials'];
        $tab = in_array($request->query('tab'), $allowedTabs, true)
            ? $request->query('tab')
            : 'tasks';

        $team = $this->resolveTeam($project);

        // Fetch pending/revision-requested deliverables from the board
        $pendingApprovals = $project->boardItems()
            ->whereIn('client_approval_status', ['pending', 'revision_requested'])
            ->with('itemable')
            ->get()
            ->map(function ($item) {
                $itemable = $item->itemable;
                if (!$itemable) return null;
                $type = array_search(get_class($itemable), \App\Models\ProjectBoardItem::MORPH_MAP, true);
                if ($type === false) return null;
                
                $title = '';
                if ($type === 'note') {
                    $title = $itemable->title ?: ($itemable->content ? mb_strimwidth($itemable->content, 0, 80, '…') : 'Sticky Note');
                } elseif ($type === 'task') {
                    $title = $itemable->task_name;
                } elseif ($type === 'report') {
                    $title = $itemable->title;
                } elseif ($type === 'todo') {
                    $title = $itemable->title ?: 'Todo';
                } elseif ($type === 'file') {
                    $title = $itemable->original_name ?: 'File';
                }
                
                return [
                    'board_item_id' => $item->id,
                    'type' => $type,
                    'id' => $itemable->id,
                    'title' => $title,
                    'for_date' => $item->for_date,
                    'client_approval_status' => $item->client_approval_status,
                    'client_feedback' => $item->client_feedback,
                ];
            })->filter()->values();

        // Support tickets
        $supportTickets = \App\Models\Ticket::where('user_id', $request->user()->id)
            ->latest()
            ->limit(5)
            ->get(['id', 'ticket_subject as subject', 'ticket_status as status', 'created_at']);

        // Activity log
        $activities = collect();
        $comments = $project->comments()->with('author:id,name')->latest()->limit(10)->get();
        foreach ($comments as $comment) {
            $activities->push([
                'type' => 'comment',
                'user' => $comment->author?->name ?? $comment->guest_name ?? 'Client',
                'description' => 'commented in discussions',
                'detail' => mb_strimwidth(strip_tags($comment->body), 0, 80, '…'),
                'time' => $comment->created_at?->toIso8601String(),
            ]);
        }
        $files = $project->files()->latest()->limit(5)->get();
        foreach ($files as $file) {
            $activities->push([
                'type' => 'file',
                'user' => 'Team',
                'description' => 'uploaded file: ' . $file->original_name,
                'detail' => $file->humanSize(),
                'time' => $file->created_at?->toIso8601String(),
            ]);
        }
        $projectActivities = $activities->sortByDesc('time')->values()->take(8);

        return Inertia::render('Client/Projects/Show', [
            'project' => fn () => (new ClientProjectResource($project))->resolve(),
            'recentReports' => fn () => $recentReports,
            'team' => fn () => $team,
            'activeTab' => fn () => $tab,
            'tabContent' => fn () => $this->loadTabContent($project, $tab),
            'pendingApprovals' => fn () => $pendingApprovals,
            'supportTickets' => fn () => $supportTickets,
            'projectActivity' => fn () => $projectActivities,
        ]);
    }

    /**
     * Best-effort project team loader. Falls back to the project owner when no
     * dedicated team relation exists on the Eloquent model.
     */
    protected function resolveTeam(Project $project): array
    {
        $members = [];

        if (method_exists($project, 'team')) {
            try {
                $members = $project->team()->get()->map(fn ($u) => [
                    'id' => (int) $u->id,
                    'name' => $u->name ?? '',
                    'avatar_url' => $u->avatar_url ?? null,
                    'role' => $u->pivot->role ?? null,
                ])->all();
            } catch (\Throwable $e) {
                $members = [];
            }
        }

        if (empty($members) && $project->relationLoaded('user') === false && method_exists($project, 'user')) {
            $owner = $project->user ?? $project->client ?? null;
            if ($owner) {
                $members = [[
                    'id' => (int) $owner->id,
                    'name' => $owner->name ?? '',
                    'avatar_url' => $owner->avatar_url ?? null,
                    'role' => 'Owner',
                ]];
            }
        }

        return $members;
    }

    protected function loadTabContent(Project $project, string $tab): array
    {
        switch ($tab) {
            case 'tasks':
                $tasks = $project->tasks()
                    ->orderByDesc('created_at')
                    ->limit(50)
                    ->get(['id', 'task_name', 'task_description', 'due_date', 'priority']);
                return ['tasks' => $tasks];

            case 'discussions':
                $comments = $project->comments()
                    ->with('author:id,name,avatar_url')
                    ->latest()
                    ->limit(50)
                    ->get()
                    ->map(function ($c) {
                        $morphMap = [
                            \App\Models\ProjectBoardNote::class => 'note',
                            \App\Models\Task::class => 'task',
                            \App\Models\ProjectReport::class => 'report',
                            \App\Models\Todo::class => 'todo',
                            \App\Models\ProjectFile::class => 'file',
                        ];
                        $type = $morphMap[$c->commentable_type] ?? 'note';
                        return [
                            'id' => $c->id,
                            'body' => $c->body,
                            'author_id' => $c->author_id,
                            'created_at' => $c->created_at,
                            'parent_id' => $c->parent_id,
                            'guest_name' => $c->guest_name,
                            'commentable_id' => $c->commentable_id,
                            'type' => $type,
                            'author' => $c->author,
                        ];
                    });
                
                // Merge project-level comments (for main chat tab)
                $projectComments = \App\Models\ProjectComment::where('project_id', $project->id)
                    ->where('commentable_type', Project::class)
                    ->with('author')
                    ->latest()
                    ->limit(50)
                    ->get()
                    ->map(fn ($c) => [
                        'id' => $c->id,
                        'body' => $c->body,
                        'author_id' => $c->author_id,
                        'created_at' => $c->created_at,
                        'parent_id' => $c->parent_id,
                        'guest_name' => $c->guest_name,
                        'commentable_id' => $c->commentable_id,
                        'type' => 'project',
                        'author' => $c->author,
                    ]);

                $allDiscussions = $comments->concat($projectComments)->sortByDesc('created_at')->values();

                return ['discussions' => $allDiscussions->toArray()];

            case 'files':
                $files = $project->files()
                    ->latest()
                    ->limit(50)
                    ->get(['id', 'original_name', 'mime', 'size', 'created_at']);
                return ['files' => $files];

            case 'financials':
                return [
                    'financials' => [
                        'budget' => (string) $project->budget,
                        'paid' => (string) $project->paid_invoices,
                        'pending' => (string) $project->pending_invoices,
                        'percentage' => (float) $project->percentage,
                    ],
                ];
        }

        return [];
    }

    public function create()
    {
        return Inertia::render('Client/Projects/Create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'project_name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $project = Project::create([
            'user_id' => $request->user()->id,
            'project_name' => $data['project_name'],
            'status' => 'open',
            'archived' => false,
            'percentage' => 0,
            'budget' => 0,
            'total_paid' => 0,
            'date_start' => now(),
            'ai_enabled' => false,
        ]);

        if (! empty($data['description'])) {
            $project->comments()->create([
                'project_id' => $project->id,
                'author_id' => $request->user()->id,
                'body' => $data['description'],
                'commentable_type' => Project::class,
                'commentable_id' => $project->id,
            ]);
        }

        return redirect()->route('client.projects.show', $project->id);
    }

    public function activateAi(Request $request, Project $project)
    {
        $this->authorize('view', $project);

        $user = $request->user();
        
        $egpCurrency = \App\Models\Currency::where('currency', 'EGP')->first();
        if (!$egpCurrency) {
            return response()->json([
                'ok' => false,
                'message' => 'EGP currency configuration not found.',
            ], 422);
        }

        $costInUserCurrency = \App\Models\CurrenciesExchange::RateToday(10.0, $egpCurrency->id, $user->currency_id);

        if ((float) $user->user_balance < $costInUserCurrency) {
            return response()->json([
                'ok' => false,
                'insufficient' => true,
                'balance' => (float) $user->user_balance,
                'required' => $costInUserCurrency,
                'message' => 'Insufficient wallet balance. You need ' . number_format($costInUserCurrency, 2) . ' ' . $user->currency_name() . ' (10 EGP) to activate the AI.',
            ], 422);
        }

        \DB::transaction(function () use ($user, $project, $costInUserCurrency) {
            \App\Models\Transaction::create([
                'user_id' => $user->id,
                'amount' => -$costInUserCurrency,
                'reason' => 'AI Project Manager Activation for project: ' . $project->project_name,
                'category' => 'other',
                'type' => 'out',
                'project_id' => $project->id,
                'currency_id' => $user->currency_id,
            ]);

            $project->update(['ai_enabled' => true]);

            \App\Helpers\BalancesHelper::UpdateBalance($user, $project);
        });

        $project->comments()->create([
            'project_id' => $project->id,
            'author_id' => null,
            'guest_name' => 'System',
            'body' => '[System: AI Project Manager activated successfully!]',
            'commentable_type' => Project::class,
            'commentable_id' => $project->id,
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'AI Project Manager activated successfully!',
        ]);
    }
}
