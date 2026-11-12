<?php

namespace Modules\AppBuilder\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\AppBuilder\Models\BuilderMessage;
use Modules\AppBuilder\Models\BuilderProject;

/**
 * A project the owner brings in for a customer who keeps asking for changes. The worker downloads it (a Google Drive
 * zip, with its GitHub repo when there is one) into the project's folder on the build PC; from then on it is a
 * delivered project: the customer asks for changes in the chat and pays for new ideas as usual. Its changes are
 * released (a pull request on GitHub, then the deploy) after every change, in batches, or when the owner says so.
 */
class ProjectImports
{
    /** Where the owner's description ends and the worker's study of the files begins, in import_profile. */
    private const STUDY_HEADING = "\n\n## The project's study (its MUSOFTWARE.md)\n\n";

    /** The heading used before the study file existed; still split on it for projects imported then. */
    private const OLD_STUDY_HEADING = "\n\n## What the worker found in the files";

    private const MAX_STUDY_CHARS = 20000;

    public function __construct(private readonly ProjectPayment $payment) {}

    /** @param array{title: string, import_url: string, locale: string, description: string, release_mode: string, release_after_changes: int, release_quiet_minutes: int, deploy_enabled: bool} $data */
    public function import(array $data, string $ip): BuilderProject
    {
        return BuilderProject::create([
                'ip' => $ip,
                'locale' => $data['locale'],
                'currency' => config('builder.pricing.currency'),
                'title' => $data['title'],
                // what the project is; the worker adds what it finds in the files
                'spec' => $data['description'],
                'import_profile' => $data['description'],
                'project_type' => 'system',
                'status' => BuilderProject::QUEUED,
                'job_kind' => BuilderProject::JOB_IMPORT,
                'paid_at' => now(),
            'import_url' => $data['import_url'],
            'release_mode' => $data['release_mode'],
            'release_after_changes' => $data['release_after_changes'],
            'release_quiet_minutes' => $data['release_quiet_minutes'],
            'deploy_enabled' => $data['deploy_enabled'],
        ]);
    }

    /**
     * The worker has the project in its folder: it is now a delivered project the customer can change. Its GitHub
     * repo is the one the project's own .git folder points to, if any.
     */
    public function imported(BuilderProject $project, string $profile, ?string $githubRepo): void
    {
        DB::transaction(function () use ($project, $profile, $githubRepo): void {
            $project->update([
                'github_repo' => $githubRepo,
                'status' => BuilderProject::READY,
                'job_kind' => BuilderProject::JOB_BUILD,
                'version' => 1,
                'released_version' => 1,
                'imported_at' => now(),
                'built_at' => now(),
                'warranty_until' => now()->addDays((int) config('builder.warranty_days')),
                'lease_expires_at' => null,
                'progress_step' => null,
                'attempts' => 0,
                'import_profile' => $this->withStudy($project, $profile),
            ]);
            $project->messages()->create([
                'role' => BuilderMessage::ASSISTANT,
                'content' => __('builder.import.welcome', ['title' => $project->title], $project->customerLanguage()),
            ]);
        });
        $this->payment->tellOwner($project, 'Imported: '.$project->title.'. Send the customer the chat link.');
    }

    /** After a change the engineer updates MUSOFTWARE.md, so the chat knows what the program has now. */
    public function updateStudy(BuilderProject $project, string $study): void
    {
        if ($project->isImported() && trim($study) !== '') {
            $project->update(['import_profile' => $this->withStudy($project, $study)]);
        }
    }

    /** The owner's description, then the latest study; an older study is replaced, never stacked. */
    private function withStudy(BuilderProject $project, string $study): string
    {
        $description = Str::before(Str::before((string) $project->import_profile, self::STUDY_HEADING), self::OLD_STUDY_HEADING);

        return trim($description.self::STUDY_HEADING.mb_substr(trim($study), 0, self::MAX_STUDY_CHARS));
    }

    /** Batch releases that are due: enough changes are waiting, or the customer has been quiet long enough. */
    public function queueDueReleases(): int
    {
        return BuilderProject::query()
            ->whereNotNull('import_url')
            ->where('release_mode', BuilderProject::RELEASE_BATCH)
            ->where('status', BuilderProject::READY)
            ->whereColumn('version', '>', 'released_version')
            // a release that failed waits for the owner's "release now", so a broken deploy is not retried every few minutes
            ->whereNull('error')
            ->get()
            ->filter(fn (BuilderProject $project) => $this->batchIsDue($project))
            ->each(fn (BuilderProject $project) => $this->queueRelease($project))
            ->count();
    }

    private function batchIsDue(BuilderProject $project): bool
    {
        if ($project->changesWaiting() >= max(1, $project->release_after_changes)) {
            return true;
        }
        $lastWord = $project->messages()->where('role', BuilderMessage::USER)->latest('id')->value('created_at');
        $quietSince = max($project->built_at?->getTimestamp() ?? 0, $lastWord ? strtotime((string) $lastWord) : 0);

        return $quietSince > 0 && $quietSince <= now()->subMinutes(max(1, $project->release_quiet_minutes))->getTimestamp();
    }

    /** Sends the delivered changes to the worker for release; only while nothing else runs for the project. */
    public function queueRelease(BuilderProject $project): bool
    {
        if (! $project->isImported() || $project->status !== BuilderProject::READY || $project->changesWaiting() === 0) {
            return false;
        }

        $project->update(['status' => BuilderProject::QUEUED, 'job_kind' => BuilderProject::JOB_RELEASE, 'paid_at' => now(), 'attempts' => 0, 'error' => null, 'progress_note' => null]);

        return true;
    }

    /** A release ended: the pull request and the deploy are recorded and the customer hears it is live. */
    public function released(BuilderProject $project, ?string $pullRequestUrl, bool $deployed, string $note): void
    {
        $project->update([
            'status' => BuilderProject::READY,
            'job_kind' => BuilderProject::JOB_BUILD,
            'released_version' => $project->version,
            'released_at' => now(),
            'pull_request_url' => $pullRequestUrl ?: $project->pull_request_url,
            'error' => null,
            'lease_expires_at' => null,
            'progress_step' => null,
            'attempts' => 0,
        ]);
        if ($deployed) {
            $project->messages()->create(['role' => BuilderMessage::TEAM, 'content' => __('builder.import.live', [], $project->customerLanguage())]);
        }
        $this->payment->tellOwner($project, 'Released: '.$project->title.($pullRequestUrl ? ' '.$pullRequestUrl : '').($deployed ? ' (deployed)' : '').' '.Str::limit($note, 500));
    }

    /**
     * An import or a release failed: the owner hears, the customer is not told a build failed. A failed import
     * waits for the owner to fix the link and retry; a failed release leaves the delivered changes waiting.
     */
    public function failed(BuilderProject $project, string $error): void
    {
        $import = $project->job_kind === BuilderProject::JOB_IMPORT;
        $project->update([
            'status' => $import ? BuilderProject::FAILED : BuilderProject::READY,
            'job_kind' => $import ? BuilderProject::JOB_IMPORT : BuilderProject::JOB_BUILD,
            'error' => mb_substr($error, 0, 4000),
            'lease_expires_at' => null,
            'progress_step' => null,
        ]);
        $this->payment->tellOwner($project, ($import ? 'Import failed: ' : 'Release failed: ').$project->title.' '.Str::limit($error, 500));
    }
}
