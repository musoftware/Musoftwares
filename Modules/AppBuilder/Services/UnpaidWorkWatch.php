<?php

namespace Modules\AppBuilder\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Modules\AppBuilder\Models\BuilderProject;
use Modules\AppBuilder\Models\BuilderRequest;

/**
 * Runs on a schedule and tells the owner about any work waiting for the engineer that no payment covers: a first
 * build with nothing paid, or a change that is neither paid nor a free change of a paid program in its free time.
 * Nothing should ever be like that; when something is, the owner hears it once, before approving it.
 */
class UnpaidWorkWatch
{
    private const REMEMBER_DAYS = 30;

    private const WAITING = [BuilderProject::QUEUED, BuilderProject::DESIGNING, BuilderProject::BUILDING];

    public function __construct(private readonly ProjectPayment $payment) {}

    /** Tells the owner about each uncovered item not reported before; returns how many are uncovered now. */
    public function check(): int
    {
        $builds = $this->unpaidFirstBuilds();
        $requests = $this->uncoveredRequests();

        $builds->each(fn (BuilderProject $project) => $this->report($project, 'b'.$project->id,
            'No payment behind this build: '.$project->title.' (paid '.$project->paid_total.' of '.$project->amount.' '.$project->currency.'). Do not approve it before checking.'));
        $requests->each(fn (BuilderRequest $request) => $this->report($request->project, 'r'.$request->id,
            'No payment behind this change: '.$request->title.' in '.$request->project->title.'. Do not approve it before checking.'));

        return $builds->count() + $requests->count();
    }

    /** @return Collection<int, BuilderProject> */
    private function unpaidFirstBuilds(): Collection
    {
        return BuilderProject::whereIn('status', self::WAITING)
            ->where('version', 0)
            ->whereNull('imported_at')
            ->where(fn ($kind) => $kind->whereNull('job_kind')->orWhere('job_kind', BuilderProject::JOB_BUILD))
            ->where('paid_total', '<=', 0)
            ->get();
    }

    /** @return Collection<int, BuilderRequest> */
    private function uncoveredRequests(): Collection
    {
        return BuilderRequest::with('project')
            ->whereIn('status', [BuilderRequest::READY, BuilderRequest::BUILDING])
            ->whereNull('paid_at')
            ->get()
            ->filter(fn (BuilderRequest $request) => $request->project !== null && ! $this->freeChangeAllowed($request));
    }

    /** A change without payment is right only when it is free and its program is paid and in its free time. */
    private function freeChangeAllowed(BuilderRequest $request): bool
    {
        return $request->free && ($request->project->isUnderWarranty() || $request->project->isOwnerImport());
    }

    private function report(BuilderProject $project, string $key, string $line): void
    {
        if (! Cache::add('builder-unpaid-watch:'.$key, true, now()->addDays(self::REMEMBER_DAYS))) {
            return;
        }

        $this->payment->tellOwner($project, $line, route('admin.builder.show', $project));
    }
}
