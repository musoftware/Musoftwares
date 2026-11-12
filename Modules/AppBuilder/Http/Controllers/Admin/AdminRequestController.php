<?php

namespace Modules\AppBuilder\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Modules\AppBuilder\Http\Requests\OwnerReviewRequest;
use Modules\AppBuilder\Models\BuilderProject;
use Modules\AppBuilder\Models\BuilderRequest;
use Modules\AppBuilder\Services\OwnerAlerts;
use Modules\AppBuilder\Services\OwnerReview;
use Modules\AppBuilder\Services\OwnerRollback;

/** The owner reads, corrects and approves (or turns down) each request before the engineer starts it. */
class AdminRequestController extends Controller
{
    public function __construct(private readonly OwnerReview $review) {}

    public function save(OwnerReviewRequest $request, BuilderProject $project, int $item): RedirectResponse
    {
        $this->review->save($this->open($project, $item), $request->changes());

        return back();
    }

    public function approve(OwnerReviewRequest $request, BuilderProject $project, int $item): RedirectResponse
    {
        $this->review->approve($project, $this->open($project, $item), $request->changes());

        return back();
    }

    public function reject(OwnerReviewRequest $request, BuilderProject $project, int $item): RedirectResponse
    {
        $this->review->reject($project, $this->open($project, $item), trim((string) $request->validated('note')));

        return back();
    }

    /** A paid new project: the owner reads (and may correct) the brief, and the first build starts. */
    public function approveProject(OwnerReviewRequest $request, BuilderProject $project): RedirectResponse
    {
        abort_unless($project->waitsForOwner(), 409);
        $this->review->approveProject($project, $request->validated('spec'));

        return back();
    }

    /** Undo a delivered change: the worker reverts its commits and releases (GitHub, deploy, the app). */
    public function rollback(BuilderProject $project, int $item, OwnerRollback $rollback): RedirectResponse
    {
        $request = $project->requests()->whereKey($item)->firstOrFail();
        abort_unless($rollback->queue($project, $request), 409, __('builder.errors.no_rollback'));

        return back();
    }

    /** A test push to every admin's devices, so the owner sees the alerts really arrive. */
    public function testPush(OwnerAlerts $alerts): RedirectResponse
    {
        $project = BuilderProject::latest('id')->firstOrFail();
        $sent = $alerts->send($project, 'Test notification from the AI builder: tap to open the projects list.', route('admin.builder.index'));
        abort_if($sent === 0, 409, 'No admin account to send to.');

        return back();
    }

    /** Only a request the engineer has not started can still be changed. */
    private function open(BuilderProject $project, int $item): BuilderRequest
    {
        return $project->requests()->whereKey($item)->whereIn('status', [BuilderRequest::UNPAID, BuilderRequest::READY])->firstOrFail();
    }
}
