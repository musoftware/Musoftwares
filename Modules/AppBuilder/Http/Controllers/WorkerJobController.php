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
        // a change is named by what it carries, not by the project's first title
        $title = app(\Modules\AppBuilder\Services\ProjectRequests::class)->buildTitle($project) ?? $project->title;
        // the owner hears on Telegram when the worker starts a job, as he hears when it ends
        app(\Modules\AppBuilder\Services\OwnerAlerts::class)->send($project, 'The worker started ('.($project->job_kind ?: BuilderProject::JOB_BUILD).', try '.$project->attempts.'): '.$title);

        return response()->json(['job' => [
            'uuid' => $project->uuid,
            'title' => $title,
            // the customer's requests in their own words, like a developer told directly; the first build has the agreed brief
            'spec' => app(\Modules\AppBuilder\Services\ProjectRequests::class)->briefFor($project) ?? $project->spec,
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
            // the owner's import (its own GitHub and server); a customer's own project is delivered as files
            'imported' => $project->isOwnerImport(),
            'customer_project' => (bool) $project->customer_project,
            'import_url' => $project->import_url,
            // a change is released inside its own build only in "after every change" mode
            'release_now' => $project->isOwnerImport() && $project->release_mode === BuilderProject::RELEASE_EACH,
            'deploy' => $project->isOwnerImport() && $project->deploy_enabled,
            // the commits of the change the owner rolls back
            'rollback' => $project->job_kind === BuilderProject::JOB_ROLLBACK ? app(\Modules\AppBuilder\Services\OwnerRollback::class)->forWorker($project) : null,
        ];
    }

    /** The worker has the imported project in its folder; the customer can start asking for changes. */
    public function imported(WorkerReportRequest $request, BuilderProject $project, \Modules\AppBuilder\Services\ProjectImports $imports): JsonResponse
    {
        $this->assertBuilding($project);
        abort_unless($project->job_kind === BuilderProject::JOB_IMPORT, 409, 'This job is not an import.');
        $imports->imported($project, $request->validated('profile'), $request->validated('github_repo'), [
            'report' => $request->validated('report'),
            'title' => $request->validated('request_title'),
            'spec' => $request->validated('request_spec'),
            'tokens' => (int) $request->validated('request_tokens'),
            'complex' => $request->boolean('request_complex'),
        ]);

        return response()->json(['status' => 'imported']);
    }

    /** The delivered changes were pushed to GitHub as a pull request and, when asked, deployed. */
    public function released(WorkerReportRequest $request, BuilderProject $project, \Modules\AppBuilder\Services\ProjectImports $imports): JsonResponse
    {
        $this->assertBuilding($project);
        abort_unless(in_array($project->job_kind, [BuilderProject::JOB_RELEASE, BuilderProject::JOB_ROLLBACK], true), 409, 'This job is not a release.');
        $imports->released($project, $request->validated('pull_request_url'), (bool) $request->validated('deployed'), (string) $request->validated('note'), $request->file('apk'));
        $imports->countReleaseCost($project, (float) $request->validated('release_cost_usd'));

        return response()->json(['status' => 'released']);
    }

    /** The last delivered version, so a change request starts from the real files. */
    public function source(BuilderProject $project): StreamedResponse
    {
        abort_unless($project->hasDelivery() && Storage::disk('local')->exists($project->archivePath()), 404);
        abort_unless($this->sourceNeeded($project), 409, 'This project is not being built or tried.');

        return Storage::disk('local')->download($project->archivePath(), 'source.zip');
    }

    /**
     * The whole conversation, so the build knows everything that was said, not only the brief.
     * Access details are already masked in it; the worker gets their values only through `secrets`.
     */
    public function chat(BuilderProject $project): JsonResponse
    {
        $this->assertBuilding($project);

        return response()->json(['messages' => $project->messages()->with('attachments')->get()->map(fn (\Modules\AppBuilder\Models\BuilderMessage $message) => [
            'id' => $message->id,
            'role' => $message->role,
            // a site tour's notes are the build's map of the site to copy
            'text' => $message->role === \Modules\AppBuilder\Models\BuilderMessage::TOUR ? $message->tourNotes() : $message->content,
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

    /**
     * The worker was closed in the middle of this build (an update, a restart): the build is free to be claimed again
     * now instead of after its lease. The try still counts, so a build that keeps getting cut off stops at the limit.
     */
    public function abandoned(BuilderProject $project): JsonResponse
    {
        $this->assertBuilding($project);
        $project->update(['lease_expires_at' => now()->subSecond()]);

        return response()->json(['status' => 'queued']);
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
            // the worker's own update from the ticked phases has no line of its own
            'caption' => ['required_without:plan', 'nullable', 'string', 'max:300'],
            'image' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp', 'max:'.(int) config('builder.limits.snapshot_kb')],
            'plan' => ['nullable', 'string', 'max:20000'],
        ]);
        // the engineer's phases (progress.md); a problem here never refuses the progress itself
        if (! empty($data['plan'])) {
            rescue(fn () => app(\Modules\AppBuilder\Services\BuildPhases::class)->update($project, (string) $data['plan']), report: true);
        }

        return $snapshots->update($project, (int) $data['percent'], strip_tags((string) ($data['caption'] ?? '')), $data['image'] ?? null)
            ? response()->json(['status' => 'ok'], 201)
            : response()->json(['status' => 'enough pictures'], 429);
    }

    /** The build's new commits, oldest first, one line each in the owner's log of this project. */
    public function commits(Request $request, BuilderProject $project, \Modules\AppBuilder\Services\WorkerActivity $activity, \Modules\AppBuilder\Services\WorkerRoles $roles): JsonResponse
    {
        $this->assertBuilding($project);
        $commits = $request->validate([
            'commits' => ['required', 'array', 'max:50'],
            'commits.*.hash' => ['required', 'string', 'regex:/^[0-9a-f]{7,64}$/'],
            'commits.*.at' => ['required', 'integer', 'min:0'],
            'commits.*.message' => ['nullable', 'string', 'max:300'],
        ])['commits'];
        foreach ($commits as $commit) {
            $activity->record($project, 'commit', substr($commit['hash'], 0, 7).' '.($commit['message'] ?? ''), $roles->roleOf($request));
        }

        return response()->json(['status' => 'ok', 'count' => count($commits)]);
    }

    public function progress(WorkerReportRequest $request, BuilderProject $project): JsonResponse
    {
        $this->assertBuilding($project);
        $this->jobs->progress($project, $request->validated('step'), $request->validated('file'), $request->validated('percent'));

        return response()->json(['status' => 'ok']);
    }

    public function complete(WorkerReportRequest $request, BuilderProject $project): JsonResponse
    {
        $this->assertBuilding($project);

        abort_unless(($project->job_kind ?: BuilderProject::JOB_BUILD) === BuilderProject::JOB_BUILD, 409, 'This job is not a build.');

        try {
            $this->jobs->complete($project, $request->file('archive')?->getRealPath(), $request->validated('summary'), (float) $request->validated('cost_usd'), (string) $request->validated('ideas'), (string) $request->validated('checks'));
        } catch (InvalidArgumentException $e) {
            Log::warning('App builder: worker sent a bad archive', ['project_id' => $project->id, 'error' => $e->getMessage()]);

            return response()->json(['message' => $e->getMessage()], 422);
        }
        $imports = app(\Modules\AppBuilder\Services\ProjectImports::class);
        // the engineer updated the project's study (MUSOFTWARE.md) with what the change added
        $imports->updateStudy($project, (string) $request->validated('study'));
        $imports->countReleaseCost($project, (float) $request->validated('release_cost_usd'));
        // "after every change": the build already pushed its pull request and deployed
        if ($project->isOwnerImport() && $request->boolean('released')) {
            $imports->released($project, $request->validated('pull_request_url'), $request->boolean('deployed'), (string) $request->validated('note'), $request->file('apk'));
        } elseif ($project->isOwnerImport() && $request->filled('release_error')) {
            // delivered, but not out: the change waits for the owner's "release now"
            $imports->failed($project, (string) $request->validated('release_error'));
        }
        // the requests this build carried are done; the ones that became ready meanwhile start now
        $notDone = array_values(array_filter(array_map('intval', explode(',', (string) $request->validated('not_done')))));
        app(\Modules\AppBuilder\Services\ProjectRequests::class)->finish($project->refresh(), $request->validated('git_from'), $request->validated('git_to'), $notDone);

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

    /**
     * The token reads a project's files only while the worker really needs them: its own build, a live trial, or a
     * ready program a build in progress starts from. A leaked token alone cannot pull every customer's program.
     */
    private function sourceNeeded(BuilderProject $project): bool
    {
        if ($project->status === BuilderProject::BUILDING || in_array($project->demo_status, [\Modules\AppBuilder\Services\LiveTrials::REQUESTED, \Modules\AppBuilder\Services\LiveTrials::LIVE], true)) {
            return true;
        }
        $starters = app(\Modules\AppBuilder\Services\Starters::class);

        return BuilderProject::where('status', BuilderProject::BUILDING)->whereNotNull('starter')->pluck('starter')
            ->contains(fn (string $slug) => ($starters->find($slug)['source_uuid'] ?? null) === $project->uuid);
    }

    private function assertBuilding(BuilderProject $project): void
    {
        abort_unless($project->status === BuilderProject::BUILDING, 409, 'This job is not being built.');
    }
}
