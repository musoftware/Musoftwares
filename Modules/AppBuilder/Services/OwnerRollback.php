<?php

namespace Modules\AppBuilder\Services;

use Modules\AppBuilder\Models\BuilderMessage;
use Modules\AppBuilder\Models\BuilderProject;
use Modules\AppBuilder\Models\BuilderRequest;

/**
 * The owner undoes a delivered change that went wrong, from the site: the worker reverts exactly the commits that
 * change made in the project's git (the history stays), then releases like a release job (GitHub, deploy, app).
 * Only an imported project, whose changes live on the customer's own server, is rolled back this way.
 */
class OwnerRollback
{
    /** Every request the same build carried goes back together, since they share the same commits. */
    public function queue(BuilderProject $project, BuilderRequest $request): bool
    {
        if (! $project->isOwnerImport() || $project->status !== BuilderProject::READY || ! $request->canRollBack()) {
            return false;
        }

        $this->sameBuild($project, $request)->update(['rollback_asked_at' => now()]);
        $project->update(['status' => BuilderProject::QUEUED, 'job_kind' => BuilderProject::JOB_ROLLBACK, 'paid_at' => now(), 'attempts' => 0, 'error' => null, 'progress_note' => null]);

        return true;
    }

    /** @return array{from: string, to: string, title: string}|null what the worker reverts */
    public function forWorker(BuilderProject $project): ?array
    {
        $request = $project->requests()->whereNotNull('rollback_asked_at')->latest('id')->first();

        return $request === null ? null : ['from' => (string) $request->git_from, 'to' => (string) $request->git_to, 'title' => (string) $request->title];
    }

    /** The revert went out: the customer reads which change was taken back. */
    public function rolledBack(BuilderProject $project): void
    {
        $requests = $project->requests()->whereNotNull('rollback_asked_at')->get();
        $project->requests()->whereNotNull('rollback_asked_at')->update(['rolled_back_at' => now(), 'rollback_asked_at' => null]);
        $project->messages()->create([
            'role' => BuilderMessage::TEAM,
            'content' => __('builder.requests.rolled_back', ['title' => $requests->pluck('title')->implode('، ')], $project->customerLanguage()),
        ]);
    }

    /** The revert could not be made (a later change touches the same lines): nothing changed, the owner can try again. */
    public function failed(BuilderProject $project): void
    {
        $project->requests()->whereNotNull('rollback_asked_at')->update(['rollback_asked_at' => null]);
    }

    private function sameBuild(BuilderProject $project, BuilderRequest $request)
    {
        return $project->requests()->where('status', BuilderRequest::DONE)->where('git_from', $request->git_from)->where('git_to', $request->git_to)->whereNull('rolled_back_at');
    }
}
