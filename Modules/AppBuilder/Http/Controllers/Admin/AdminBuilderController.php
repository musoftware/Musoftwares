<?php

namespace Modules\AppBuilder\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
            ->when($request->boolean('waiting'), fn ($query) => $query->where('awaiting_answer', true))
            ->latest('updated_at')
            ->paginate(self::PER_PAGE, ['id', 'uuid', 'title', 'status', 'awaiting_answer', 'amount', 'paid_total', 'currency', 'build_cost_usd', 'chat_input_tokens', 'chat_output_tokens', 'client_name', 'updated_at'])
            ->withQueryString();

        return Inertia::render('Admin/Builder/Index', ['projects' => $projects, 'waitingOnly' => $request->boolean('waiting')]);
    }

    public function show(BuilderProject $project): Response
    {
        $project->load('messages.attachments');

        return Inertia::render('Admin/Builder/Show', [
            'project' => $project->only(['uuid', 'title', 'status', 'design_fee', 'design_url', 'awaiting_answer', 'amount', 'paid_total', 'currency', 'build_cost_usd', 'budget_usd', 'build_model', 'client_name', 'client_email', 'client_whatsapp', 'spec', 'error', 'version']),
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
        ]);

        return back()->with('success', __('builder.admin.retried'));
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
