<?php

namespace Modules\AppBuilder\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Modules\AppBuilder\Http\Requests\WorkerReportRequest;
use Modules\AppBuilder\Models\BuilderAttachment;
use Modules\AppBuilder\Models\BuilderProject;
use Illuminate\Http\Request;
use Modules\AppBuilder\Services\BuildQuestions;
use Modules\AppBuilder\Services\JobQueue;
use Modules\AppBuilder\Services\ProjectSecrets;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** The API the owner's worker polls. The worker only ever calls out; nothing here calls the worker. */
class WorkerJobController extends Controller
{
    public function __construct(
        private readonly JobQueue $jobs,
        private readonly BuildQuestions $questions,
        private readonly ProjectSecrets $secrets,
        private readonly \Modules\AppBuilder\Services\OriginalFiles $originals,
    ) {}

    public function claim(Request $request): JsonResponse|Response
    {
        // a backup worker waits while the main one is alive, so two PCs never split the work
        if (! app(\Modules\AppBuilder\Services\WorkerRoles::class)->mayTakeNewWork($request)) {
            return response()->noContent();
        }

        $project = $this->jobs->claim();
        if (! $project) {
            return response()->noContent();
        }

        return response()->json(['job' => [
            'uuid' => $project->uuid,
            'title' => $project->title,
            'spec' => $project->spec,
            'locale' => $project->customerLanguage(),
            'budget_usd' => (float) $project->budget_usd,
            'estimated_tokens' => $project->estimated_tokens,
            'model' => $project->build_model,
            'design_url' => $project->design_url,
            // access details go only to the worker, over this token-protected API, and only for this job
            'secrets' => $this->secrets->forWorker($project),
            'attachments' => $this->originals->forBuild($project)->map(fn (BuilderAttachment $file) => [
                'id' => $file->id,
                'name' => $file->original_name,
                'mime' => $file->mime,
            ])->values(),
            'has_previous_version' => $project->hasDelivery(),
            // the first build copies this ready program from the build machine and develops it into the idea
            'starter' => $project->hasDelivery() ? null : $project->starter,
            // a program learned from an earlier paid project: the worker copies that project's code, cleaned
            'starter_source' => $project->hasDelivery() ? null : (app(\Modules\AppBuilder\Services\Starters::class)->find($project->starter)['source_uuid'] ?? null),
        ] + $this->importDetails($project)]);
    }

    /** For a project the owner imported: where it comes from, its GitHub repo, and how its changes are released. */
    private function importDetails(BuilderProject $project): array
    {
        return [
            'kind' => $project->job_kind ?: BuilderProject::JOB_BUILD,
            'imported' => $project->isImported(),
            'import_url' => $project->import_url,
            'github_repo' => $project->github_repo,
            // a change is released inside its own build only in "after every change" mode
            'release_now' => $project->isImported() && $project->release_mode === BuilderProject::RELEASE_EACH,
            'deploy' => $project->isImported() && $project->deploy_enabled,
            'deploy_command' => $project->deploy_command,
        ];
    }

    /** The worker has the imported project in its folder; the customer can start asking for changes. */
    public function imported(WorkerReportRequest $request, BuilderProject $project, \Modules\AppBuilder\Services\ProjectImports $imports): JsonResponse
    {
        $this->assertBuilding($project);
        abort_unless($project->job_kind === BuilderProject::JOB_IMPORT, 409, 'This job is not an import.');
        $imports->imported($project, $request->validated('profile'));

        return response()->json(['status' => 'imported']);
    }

    /** The delivered changes were pushed to GitHub as a pull request and, when asked, deployed. */
    public function released(WorkerReportRequest $request, BuilderProject $project, \Modules\AppBuilder\Services\ProjectImports $imports): JsonResponse
    {
        $this->assertBuilding($project);
        abort_unless($project->job_kind === BuilderProject::JOB_RELEASE, 409, 'This job is not a release.');
        $imports->released($project, $request->validated('pull_request_url'), (bool) $request->validated('deployed'), (string) $request->validated('note'));

        return response()->json(['status' => 'released']);
    }

    /** The last delivered version, so a change request starts from the real files. */
    public function source(BuilderProject $project): StreamedResponse
    {
        abort_unless($project->hasDelivery() && Storage::disk('local')->exists($project->archivePath()), 404);

        return Storage::disk('local')->download($project->archivePath(), 'source.zip');
    }

    /**
     * The whole conversation, so the build knows everything that was said, not only the brief.
     * Access details are already masked in it; the worker gets their values only through `secrets`.
     */
    public function chat(BuilderProject $project): JsonResponse
    {
        return response()->json(['messages' => $project->messages()->with('attachments')->get()->map(fn (\Modules\AppBuilder\Models\BuilderMessage $message) => [
            'id' => $message->id,
            'role' => $message->role,
            'text' => $message->content,
            'files' => $message->attachments->pluck('original_name')->values(),
            'at' => $message->created_at?->toIso8601String(),
        ])->values()]);
    }

    /** A file the customer sent in the chat. Only files of this project are given out. */
    public function attachment(BuilderProject $project, BuilderAttachment $attachment): StreamedResponse
    {
        abort_unless($attachment->project_id === $project->id && Storage::disk('local')->exists($attachment->path), 404);

        return Storage::disk('local')->download($attachment->path, 'file-'.$attachment->id);
    }

    /** The build needs something from the customer or the team, and waits for the answer. */
    public function question(Request $request, BuilderProject $project): JsonResponse
    {
        $this->assertBuilding($project);
        $text = trim((string) $request->validate(['question' => ['required', 'string', 'max:3000']])['question']);

        return response()->json(['message_id' => $this->questions->ask($project, $text)->id]);
    }

    /** Answers since the question, plus the access details as they are now (the team may have just added one). */
    public function answers(Request $request, BuilderProject $project): JsonResponse
    {
        $this->assertBuilding($project);
        $this->jobs->progress($project, 'waiting', null);

        return response()->json([
            'answers' => $this->questions->answersAfter($project, (int) $request->query('after', 0)),
            'secrets' => $this->secrets->forWorker($project),
        ]);
    }

    /** How much is done, one plain line, and a screenshot when there is something new to see. */
    public function snapshot(Request $request, BuilderProject $project, \Modules\AppBuilder\Services\BuildSnapshots $snapshots): JsonResponse
    {
        $this->assertBuilding($project);
        $data = $request->validate([
            'percent' => ['required', 'integer', 'min:0', 'max:100'],
            'caption' => ['required', 'string', 'max:300'],
            'image' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp', 'max:'.(int) config('builder.limits.snapshot_kb')],
        ]);

        return $snapshots->update($project, (int) $data['percent'], strip_tags($data['caption']), $data['image'] ?? null)
            ? response()->json(['status' => 'ok'], 201)
            : response()->json(['status' => 'enough pictures'], 429);
    }

    public function progress(WorkerReportRequest $request, BuilderProject $project): JsonResponse
    {
        $this->assertBuilding($project);
        $this->jobs->progress($project, $request->validated('step'), $request->validated('file'));

        return response()->json(['status' => 'ok']);
    }

    public function complete(WorkerReportRequest $request, BuilderProject $project): JsonResponse
    {
        $this->assertBuilding($project);

        abort_unless(($project->job_kind ?: BuilderProject::JOB_BUILD) === BuilderProject::JOB_BUILD, 409, 'This job is not a build.');

        try {
            $this->jobs->complete($project, $request->file('archive')?->getRealPath(), $request->validated('summary'), (float) $request->validated('cost_usd'), (string) $request->validated('ideas'));
        } catch (InvalidArgumentException $e) {
            Log::warning('App builder: worker sent a bad archive', ['project_id' => $project->id, 'error' => $e->getMessage()]);

            return response()->json(['message' => $e->getMessage()], 422);
        }
        // "after every change": the build already pushed its pull request and deployed
        $imports = app(\Modules\AppBuilder\Services\ProjectImports::class);
        if ($project->isImported() && $request->boolean('released')) {
            $imports->released($project, $request->validated('pull_request_url'), $request->boolean('deployed'), (string) $request->validated('note'));
        } elseif ($project->isImported() && $request->filled('release_error')) {
            // delivered, but not out: the change waits for the owner's "release now"
            $imports->failed($project, (string) $request->validated('release_error'));
        }

        return response()->json(['status' => 'delivered', 'version' => $project->refresh()->version]);
    }

    public function fail(WorkerReportRequest $request, BuilderProject $project): JsonResponse
    {
        $this->assertBuilding($project);
        // an import or a release that fails is the owner's matter; a failed build also tells the customer
        ($project->job_kind ?: BuilderProject::JOB_BUILD) === BuilderProject::JOB_BUILD
            ? $this->jobs->fail($project, $request->validated('error'), (float) $request->validated('cost_usd'))
            : app(\Modules\AppBuilder\Services\ProjectImports::class)->failed($project, $request->validated('error'));

        return response()->json(['status' => 'recorded']);
    }

    private function assertBuilding(BuilderProject $project): void
    {
        abort_unless($project->status === BuilderProject::BUILDING, 409, 'This job is not being built.');
    }
}
