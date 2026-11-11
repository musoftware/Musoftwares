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

    public function __construct(private readonly ProjectSecrets $secrets, private readonly BuildQuestions $questions) {}

    public function index(Request $request): Response
    {
        $projects = BuilderProject::query()
            ->with(['user', 'referrer'])
            ->when($request->boolean('waiting'), fn ($query) => $query->where('awaiting_answer', true))
            ->latest('updated_at')
            ->paginate(self::PER_PAGE, ['id', 'uuid', 'title', 'status', 'awaiting_answer', 'amount', 'paid_total', 'currency', 'build_cost_usd', 'chat_input_tokens', 'chat_output_tokens', 'client_name', 'ip', 'country', 'user_agent', 'user_id', 'referrer_user_id', 'created_at', 'updated_at'])
            ->withQueryString()
            ->through(fn (BuilderProject $project) => $project->only(['id', 'uuid', 'title', 'status', 'awaiting_answer', 'amount', 'paid_total', 'currency', 'build_cost_usd', 'client_name'])
                + ['updated_at' => $project->updated_at?->timezone('Africa/Cairo')->toDateTimeString(), 'chat_cost_usd' => $project->chatCostUsd(), 'visitor' => $this->visitor($project)]);

        return Inertia::render('Admin/Builder/Index', [
            'projects' => $projects,
            'waitingOnly' => $request->boolean('waiting'),
            'learning' => app(\Modules\AppBuilder\Services\BuildCostHistory::class)->summary(),
            'defaultFactor' => (float) config('builder.pricing.work_factor'),
        ]);
    }

    public function show(BuilderProject $project): Response
    {
        $project->load(['messages.attachments', 'user', 'referrer']);

        return Inertia::render('Admin/Builder/Show', [
            'project' => $project->only(['uuid', 'title', 'status', 'design_fee', 'design_url', 'awaiting_answer', 'amount', 'leave_discount_percent', 'leave_discount_usd', 'paid_total', 'quote_paid', 'price_max', 'currency', 'build_cost_usd', 'budget_usd', 'build_model', 'client_name', 'client_email', 'client_whatsapp', 'spec', 'error', 'version'])
                + ['chat_cost_usd' => $project->chatCostUsd(), 'visitor' => $this->visitor($project)],
            'messages' => $project->messages->map(fn (BuilderMessage $message) => [
                'id' => $message->id,
                'role' => $message->role,
                'text' => $message->content,
                'question' => (bool) ($message->payload['question'] ?? false),
                'files' => $message->attachments->pluck('original_name')->values(),
                'at' => $message->created_at?->toDateTimeString(),
            ])->values(),
            'secrets' => $this->secrets->listing($project),
            'chatUrl' => route('builder.show', $project),
        ]);
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

    /**
     * A paid build that failed goes back to the worker. The worker starts from the project's folder on the drive,
     * so the work already done is kept and the build only finishes what is left.
     */
    public function retry(BuilderProject $project): RedirectResponse
    {
        abort_unless($project->status === BuilderProject::FAILED, 409);
        $project->update([
            'status' => BuilderProject::QUEUED,
            'attempts' => 0,
            'error' => null,
            'lease_expires_at' => null,
            'progress_step' => null,
            'progress_file' => null,
            'progress_note' => null,
        ]);

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

    /** The work turned out bigger: ask the customer for more, up to the top of the range they saw. */
    public function extra(Request $request, BuilderProject $project, \Modules\AppBuilder\Services\ProjectPayment $payment): RedirectResponse
    {
        $extra = (float) $request->validate(['amount' => ['required', 'numeric', 'min:1', 'max:100000']])['amount'];
        abort_if($payment->askForMore($project, $extra) <= 0, 409);

        return back()->with('success', __('builder.admin.extra_asked'));
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
}
