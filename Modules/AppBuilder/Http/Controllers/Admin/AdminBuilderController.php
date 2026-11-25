<?php

namespace Modules\AppBuilder\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\AppBuilder\Models\BuilderMessage;
use Modules\AppBuilder\Models\BuilderProject;
use Modules\AppBuilder\Models\BuilderSecret;
use Modules\AppBuilder\Services\BuildQuestions;
use Modules\AppBuilder\Services\ProjectSecrets;

/**
 * The owner's view of the AI builder: every chat, what it cost, and a way to step in when the customer or the
 * AI is stuck, by writing as the team or adding an access detail (SSH, FTP, a key).
 */
class AdminBuilderController extends Controller
{
    private const PER_PAGE = 30;

    /** Messages people wrote: the customer, the AI and the team (not build progress or tour lines). */
    private const TALK_ROLES = [BuilderMessage::USER, BuilderMessage::ASSISTANT, BuilderMessage::TEAM];

    /** When a chat last had a real message, or when it started if it has none. */
    private const ACTIVE_AT = 'coalesce((select max(m.created_at) from builder_messages m where m.project_id = builder_projects.id and m.role in (?, ?, ?)), builder_projects.created_at)';

    public function __construct(
        private readonly ProjectSecrets $secrets,
        private readonly BuildQuestions $questions,
        private readonly \Modules\AppBuilder\Services\AdminChatViews $views,
    ) {}

    public function index(Request $request): Response
    {
        $chats = app(\Modules\AppBuilder\Services\AdminChats::class);
        $tab = in_array($request->query('tab'), $chats::TABS, true) ? (string) $request->query('tab') : 'customers';
        $projects = $chats->tab(BuilderProject::query(), $tab)
            ->with(['user', 'referrer'])
            ->when($request->boolean('waiting'), fn ($query) => $query->where('awaiting_answer', true))
            ->select(['id', 'uuid', 'title', 'status', 'awaiting_answer', 'amount', 'paid_total', 'currency', 'build_cost_usd', 'chat_input_tokens', 'chat_output_tokens', 'client_name', 'ip', 'country', 'user_agent', 'user_id', 'referrer_user_id', 'created_at', 'updated_at'])
            ->addSelect(['last_customer_at' => BuilderMessage::select('created_at')->whereColumn('project_id', 'builder_projects.id')->where('role', BuilderMessage::USER)->latest('id')->limit(1)])
            // newest conversation first, by its last real message: updated_at also moves when nobody wrote anything
            ->selectRaw(self::ACTIVE_AT.' as active_at', self::TALK_ROLES)
            ->orderByRaw(self::ACTIVE_AT.' desc', self::TALK_ROLES)
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();
        $seen = $this->views->seenTimes((int) $request->user()->id, $projects->pluck('id')->all());
        $projects->through(fn (BuilderProject $project) => $this->listRow($project, $seen[$project->id] ?? null));

        return Inertia::render('Admin/Builder/Index', [
            'projects' => $projects,
            'today' => now('Africa/Cairo')->toDateString(),
            'waitingOnly' => $request->boolean('waiting'),
            'tab' => $tab,
            'tabCounts' => $chats->counts(),
            'learning' => app(\Modules\AppBuilder\Services\BuildCostHistory::class)->summary(),
            'deliveryStats' => app(\Modules\AppBuilder\Services\DeliveryStats::class)->summary(),
            'defaultFactor' => (float) config('builder.pricing.work_factor'),
        ]);
    }

    /** One chat in the list; is_new when the customer wrote after this admin last opened it. */
    private function listRow(BuilderProject $project, ?\Carbon\CarbonInterface $seenAt): array
    {
        $lastCustomerAt = $project->last_customer_at ? \Illuminate\Support\Carbon::parse($project->last_customer_at) : null;

        return $project->only(['id', 'uuid', 'title', 'status', 'awaiting_answer', 'amount', 'paid_total', 'currency', 'build_cost_usd', 'client_name']) + [
            'active_at' => \Illuminate\Support\Carbon::parse($project->active_at)->timezone('Africa/Cairo')->toDateTimeString(),
            'is_new' => $this->views->isNew($lastCustomerAt, $seenAt),
            'chat_cost_usd' => $project->chatCostUsd(),
            'visitor' => $this->visitor($project),
        ];
    }

    public function show(Request $request, BuilderProject $project): Response
    {
        $this->views->markSeen((int) $request->user()->id, (int) $project->id);
        $project->load(['messages.attachments', 'user', 'referrer']);

        return Inertia::render('Admin/Builder/Show', [
            'project' => $project->only(['uuid', 'title', 'status', 'design_fee', 'design_url', 'awaiting_answer', 'amount', 'leave_discount_percent', 'leave_discount_usd', 'paid_total', 'quote_paid', 'price_max', 'currency', 'build_cost_usd', 'budget_usd', 'build_model', 'client_name', 'client_email', 'client_whatsapp', 'spec', 'error', 'version'])
                + ['chat_cost_usd' => $project->chatCostUsd(), 'visitor' => $this->visitor($project), 'waits_for_owner' => $project->waitsForOwner(), 'chat_banned' => app(\Modules\AppBuilder\Services\ChatBans::class)->isBanned($project->user_id, $project->ip)],
            'messages' => $project->messages->map(fn (BuilderMessage $message) => [
                'id' => $message->id,
                'role' => $message->role,
                'text' => $message->content,
                'question' => (bool) ($message->payload['question'] ?? false),
                'files' => $message->attachments->map(fn ($file) => ['name' => $file->original_name, 'url' => route('admin.builder.file', [$project, $file->id])])->values(),
                'at' => $message->created_at?->toDateTimeString(),
            ])->values(),
            'secrets' => $this->secrets->listing($project),
            'chatUrl' => route('builder.show', $project),
            'import' => $this->importState($project),
            'requests' => $this->requestsToReview($project),
            'workerEvents' => fn () => app(\Modules\AppBuilder\Services\WorkerActivity::class)->forAdmin($project),
        ]);
    }

    /** Every request with exactly what the engineer will read, newest first; the owner approves the open ones. */
    private function requestsToReview(BuilderProject $project): array
    {
        if (! $project->requests()->exists()) {
            return [];
        }
        $stages = new \Modules\AppBuilder\Services\RequestStages($project);

        // a payment that opens more free changes is not work: nothing for the owner to review
        return $project->requests()->where('kind', '!=', \Modules\AppBuilder\Models\BuilderRequest::PACK)->latest('id')->limit(30)->get()->map(fn (\Modules\AppBuilder\Models\BuilderRequest $item) => [
            'id' => $item->id,
            'title' => $item->title,
            'kind' => $item->kind,
            'status' => $item->status,
            'free' => $item->free,
            'amount' => (float) $item->amount,
            'paid' => $item->paid_at !== null,
            'model' => $item->model,
            'spec' => $item->spec,
            'customer_words' => $item->customer_words,
            'approved' => $item->isApproved(),
            // a delivered change of an imported project can be undone from here
            'can_rollback' => $project->isOwnerImport() && $item->canRollBack(),
            'rolling_back' => $item->rollback_asked_at !== null,
            'rolled_back' => $item->rolled_back_at !== null,
            'version' => $item->built_in_version,
            'stages' => $stages->of($item),
            'open' => in_array($item->status, [\Modules\AppBuilder\Models\BuilderRequest::UNPAID, \Modules\AppBuilder\Models\BuilderRequest::READY], true),
            'at' => $item->created_at?->timezone('Africa/Cairo')->toDateTimeString(),
        ])->values()->all();
    }

    /** An imported project's release rule, what waits to go out, and the last pull request. */
    private function importState(BuilderProject $project): ?array
    {
        if (! $project->isOwnerImport()) {
            return null;
        }

        return [
            'github_repo' => $project->github_repo,
            'release_mode' => $project->release_mode,
            'release_after_changes' => $project->release_after_changes,
            'release_quiet_minutes' => $project->release_quiet_minutes,
            'deploy_enabled' => $project->deploy_enabled,
            // the build deploys by itself only with a way into the server among the access details
            'server_access' => $project->secrets()->whereIn('name', ['ssh_host', 'ftp_host'])->exists(),
            'version' => (int) $project->version,
            'released_version' => (int) $project->released_version,
            'imported_at' => $project->imported_at?->timezone('Africa/Cairo')->toDateTimeString(),
            'released_at' => $project->released_at?->timezone('Africa/Cairo')->toDateTimeString(),
            'changes_waiting' => $project->changesWaiting(),
            'pull_request_url' => $project->pull_request_url,
            'can_release' => $project->status === BuilderProject::READY,
        ];
    }

    /** Who started the chat: the signed-in account if any, the address and device, and whose link they came from. */
    private function visitor(BuilderProject $project): array
    {
        return [
            'ip' => $project->ip,
            'country' => $project->country,
            'user_agent' => $project->user_agent,
            'started_at' => $project->created_at?->timezone('Africa/Cairo')->toDateTimeString(),
            'user' => $this->person($project->user),
            'referrer' => $this->person($project->referrer),
        ];
    }

    /** @return array{id: int, name: string, email: string|null, phone: string|null, url: string}|null */
    private function person(?\App\Models\User $user): ?array
    {
        if ($user === null) {
            return null;
        }

        return [
            'id' => $user->id,
            'name' => (string) $user->name,
            'email' => $user->email,
            'phone' => $user->mobile_1 ?: $user->whatsapp_number ?: $user->phone ?: null,
            'url' => route('admin.users.show', $user->id),
        ];
    }

    /** A line from the team. While the build waits it is the answer; otherwise the chat reads it on the next turn. */
    public function message(Request $request, BuilderProject $project): RedirectResponse
    {
        $text = trim((string) $request->validate(['text' => ['required', 'string', 'max:3000']])['text']);
        // the owner stepped into a build: that delivery does not count as done without a person
        if (in_array($project->status, [BuilderProject::QUEUED, BuilderProject::BUILDING, BuilderProject::FAILED], true)) {
            $project->update(['owner_touched' => true]);
        }

        $project->isWaitingForAnswer()
            ? $this->questions->answer($project, $text, BuilderMessage::TEAM)
            : $project->messages()->create(['role' => BuilderMessage::TEAM, 'content' => $this->secrets->mask($project, $text)]);

        return back()->with('success', __('builder.admin.sent'));
    }

    /** The designer's work is ready: the link to the design starts the build of a project that waits for it. */
    public function design(Request $request, BuilderProject $project): RedirectResponse
    {
        $url = $request->validate(['design_url' => ['required', 'url:https', 'max:500']])['design_url'];
        $project->update(['design_url' => $url]);
        if ($project->status === BuilderProject::DESIGNING) {
            $project->update(['status' => BuilderProject::QUEUED, 'attempts' => 0]);
        }

        return back()->with('success', __('builder.admin.design_saved'));
    }

    /** A paid build that failed goes back to the worker, keeping the work already done. */
    public function retry(BuilderProject $project): RedirectResponse
    {
        abort_unless($project->status === BuilderProject::FAILED, 409);
        $project->update(['owner_touched' => true]);
        app(\Modules\AppBuilder\Services\JobQueue::class)->requeue($project);

        return back()->with('success', __('builder.admin.retried'));
    }

    /** The owner marks the open price as paid, from the customer's chat page; the build starts as after a real payment. */
    public function markPaid(Request $request, BuilderProject $project, \Modules\AppBuilder\Services\ProjectPayment $payment): JsonResponse
    {
        if (! $payment->markPaidByAdmin($project, (int) $request->user()->id)) {
            return response()->json(['message' => __('builder.errors.no_quote')], 409);
        }

        return response()->json(new \Modules\AppBuilder\Http\Resources\ProjectStateResource($project->refresh()->load('messages.attachments')));
    }

    /** The owner marks the requests ticked in the customer's list as paid (paid by hand, or a test). */
    public function markRequestsPaid(Request $request, BuilderProject $project, \Modules\AppBuilder\Services\ProjectRequests $requests): JsonResponse
    {
        $ids = array_map('intval', explode(',', (string) $request->validate(['ids' => ['required', 'string', 'max:200', 'regex:/^\d+(,\d+)*$/']])['ids']));
        if ($requests->markPaid($project, $ids, \Modules\AppBuilder\Services\ProjectPayment::ADMIN_REFERENCE.$request->user()->id.'-'.now()->timestamp) === 0) {
            return response()->json(['message' => __('builder.errors.no_requests')], 409);
        }

        return response()->json(new \Modules\AppBuilder\Http\Resources\ProjectStateResource($project->refresh()->load('messages.attachments')));
    }

    /** The work turned out bigger: ask the customer for more, up to the top of the range they saw. */
    public function extra(Request $request, BuilderProject $project, \Modules\AppBuilder\Services\ProjectPayment $payment): RedirectResponse
    {
        $extra = (float) $request->validate(['amount' => ['required', 'numeric', 'min:1', 'max:100000']])['amount'];
        abort_if($payment->askForMore($project, $extra) <= 0, 409);

        return back()->with('success', __('builder.admin.extra_asked'));
    }

    /** The owner closes this visitor's chat, or opens it again after the chat closed it. */
    public function chatBan(Request $request, BuilderProject $project, \Modules\AppBuilder\Services\ChatBans $bans): RedirectResponse
    {
        $request->validate(['banned' => ['required', 'boolean']])['banned'] ? $bans->ban($project) : $bans->lift($project);

        return back()->with('success', __('builder.admin.secret_saved'));
    }

    public function storeSecret(Request $request, BuilderProject $project): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60', 'regex:/^[a-zA-Z][a-zA-Z0-9_ ]*$/'],
            'value' => ['required', 'string', 'max:10000'],
        ]);
        $this->secrets->save($project, [$data], BuilderSecret::FROM_TEAM);

        return back()->with('success', __('builder.admin.secret_saved'));
    }

    public function destroySecret(BuilderProject $project, BuilderSecret $secret): RedirectResponse
    {
        abort_unless($secret->project_id === $project->id, 404);
        $secret->delete();

        return back()->with('success', __('builder.admin.secret_removed'));
    }

    /** The file as the customer sent it, under its own name; a picture or a PDF opens in the browser. */
    public function file(BuilderProject $project, int $attachment): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $file = $project->attachments()->findOrFail($attachment);
        abort_unless(\Illuminate\Support\Facades\Storage::disk('local')->exists($file->path), 404);
        $inline = $file->isImage() || $file->mime === \Modules\AppBuilder\Models\BuilderAttachment::PDF;

        return \Illuminate\Support\Facades\Storage::disk('local')->response($file->path, $file->original_name, ['Content-Type' => $file->mime], $inline ? 'inline' : 'attachment');
    }
}
