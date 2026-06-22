<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Freelance\Models\Job;
use Inertia\Inertia;
use Modules\Freelance\Domains\Finance\Actions\AddPointsAction;

class FreelanceJobController extends Controller
{
    public function index(Request $request)
    {
        $query = Job::with(['client', 'currency'])->withCount('proposals');

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where('title', 'like', "%{$search}%")
                  ->orWhereHas('client', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
        }
        
        if ($request->has('status') && $request->get('status') !== 'all') {
            $query->where('status', $request->get('status'));
        }

        $jobs = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        return Inertia::render('Admin/Freelance/Jobs/Index', [
            'jobs' => $jobs,
            'filters' => $request->only('search', 'status')
        ]);
    }

    public function show(Job $job)
    {
        $job->load(['client', 'skills', 'proposals.freelancer', 'proposals.currency', 'contracts.freelancer', 'contracts.currency', 'currency']);

        return Inertia::render('Admin/Freelance/Jobs/Show', [
            'job' => $job
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Freelance/Jobs/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'budget' => 'nullable|numeric|min:0',
            'client_id' => 'required|exists:users,id',
            'duration' => 'nullable|string|max:255',
            'type' => 'nullable|string|in:fixed,hourly',
        ]);

        $validated['status'] = 'open';

        $job = Job::create($validated);

        return redirect()->route('admin.freelance.jobs.show', $job->id)
                         ->with('success', __('freelance.job_posted_success'));
    }

    public function edit(Job $job)
    {
        return Inertia::render('Admin/Freelance/Jobs/Edit', [
            'job' => $job
        ]);
    }

    public function update(Request $request, Job $job)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'budget' => 'nullable|numeric|min:0',
        ]);

        $job->update($validated);

        return redirect()->route('admin.freelance.jobs.show', $job->id)
                         ->with('success', __('freelance.job_updated_success'));
    }

    public function updateStatus(Request $request, Job $job)
    {
        $request->validate([
            'status' => 'required|string|in:draft,open,in_progress,completed,cancelled,suspended'
        ]);

        $job->update(['status' => $request->status]);

        if ($request->status === 'cancelled') {
            app(\Modules\Freelance\Domains\Proposal\Actions\RejectPendingProposalsAction::class)
                ->execute($job, 'Job Cancelled by Admin');
        }

        return back()->with('success', __('freelance.job_status_updated'));
    }

    public function destroy(Job $job)
    {
        app(\Modules\Freelance\Domains\Proposal\Actions\RejectPendingProposalsAction::class)
            ->execute($job, 'Job Deleted by Admin');
            
        $job->delete();
        return redirect()->route('admin.freelance.jobs.index')->with('success', __('freelance.job_deleted'));
    }

    public function forceRefund(Job $job, AddPointsAction $addPointsAction)
    {
        if (!$job->client_id) {
            return back()->with('error', __('freelance.client_not_found'));
        }

        $refundAmount = 25 + ($job->min_proposal_points ?? 0);

        try {
            $addPointsAction->execute(
                $job->client_id,
                $refundAmount,
                __('freelance.admin_forced_refund', ['job' => $job->title]),
                'job_refund',
                $job->id
            );
            
            $job->update(['status' => 'cancelled']);
            $job->contracts()->whereNotIn('status', ['completed', 'cancelled'])->update(['status' => 'cancelled']);
            
            app(\Modules\Freelance\Domains\Proposal\Actions\RejectPendingProposalsAction::class)
                ->execute($job, 'Job Cancelled & Force Refunded by Admin');
            
            return back()->with('success', __('freelance.points_refunded_success', ['amount' => $refundAmount]));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
