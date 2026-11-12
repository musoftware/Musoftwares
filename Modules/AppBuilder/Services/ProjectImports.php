<?php

namespace Modules\AppBuilder\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\AppBuilder\Models\BuilderMessage;
use Modules\AppBuilder\Models\BuilderProject;
use Modules\AppBuilder\Models\BuilderSecret;

/**
 * A project the owner brings in for a customer who keeps asking for changes. The worker downloads it (a Google Drive
 * zip, with its GitHub repo when there is one) into the project's folder on the build PC; from then on it is a
 * delivered project: the customer asks for changes in the chat and pays for new ideas as usual. Its changes are
 * released (a pull request on GitHub, then the deploy) after every change, in batches, or when the owner says so.
 */
class ProjectImports
{
    public function __construct(private readonly ProjectSecrets $secrets, private readonly ProjectPayment $payment) {}

    /** @param array{title: string, import_url: string, github_repo?: ?string, github_token?: ?string, locale: string, description: string, release_mode: string, release_after_changes: int, release_quiet_minutes: int, deploy_enabled: bool, deploy_command?: ?string} $data */
    public function import(array $data, string $ip): BuilderProject
    {
        return DB::transaction(function () use ($data, $ip): BuilderProject {
            $project = BuilderProject::create([
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
                'github_repo' => $this->repoPath($data['github_repo'] ?? null),
                'release_mode' => $data['release_mode'],
                'release_after_changes' => $data['release_after_changes'],
                'release_quiet_minutes' => $data['release_quiet_minutes'],
                'deploy_enabled' => $data['deploy_enabled'],
                'deploy_command' => $data['deploy_command'] ?? null,
            ]);
            // the token goes only to the worker, which keeps it out of the build's sandbox
            $this->secrets->save($project, [['name' => 'github_token', 'value' => (string) ($data['github_token'] ?? '')]], BuilderSecret::FROM_TEAM);

            return $project;
        });
    }

    /** "https://github.com/owner/repo(.git)" becomes "owner/repo"; anything else is refused by the form before this. */
    public function repoPath(?string $url): ?string
    {
        if ($url === null || ! preg_match('#github\.com[/:]([A-Za-z0-9_.-]+)/([A-Za-z0-9_.-]+?)(?:\.git)?/?$#', trim($url), $m)) {
            return null;
        }

        return $m[1].'/'.$m[2];
    }

    /** The worker has the project in its folder: it is now a delivered project the customer can change. */
    public function imported(BuilderProject $project, string $profile): void
    {
        DB::transaction(function () use ($project, $profile): void {
            $project->update([
                'status' => BuilderProject::READY,
                'job_kind' => BuilderProject::JOB_BUILD,
                'version' => 1,
                'released_version' => 1,
                'imported_at' => now(),
                'built_at' => now(),
                'lease_expires_at' => null,
                'progress_step' => null,
                'attempts' => 0,
                'import_profile' => trim($project->import_profile."\n\n## What the worker found in the files\n\n".mb_substr($profile, 0, 20000)),
            ]);
            $project->messages()->create([
                'role' => BuilderMessage::ASSISTANT,
                'content' => __('builder.import.welcome', ['title' => $project->title], $project->customerLanguage()),
            ]);
        });
        $this->payment->tellOwner($project, 'Imported: '.$project->title.'. Send the customer the chat link.');
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
