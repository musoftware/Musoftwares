<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Freelance\Models\Job;
use Inertia\Inertia;

class FreelanceJobController extends Controller
{
    public function index(Request $request)
    {
        $query = Job::with('client')->withCount('proposals');

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
        $job->load(['client', 'skills', 'proposals.freelancer']);

        return Inertia::render('Admin/Freelance/Jobs/Show', [
            'job' => $job
        ]);
    }

    public function updateStatus(Request $request, Job $job)
    {
        $request->validate([
            'status' => 'required|string|in:draft,published,in_progress,completed,cancelled,suspended'
        ]);

        $job->update(['status' => $request->status]);

        return back()->with('success', 'Job status updated successfully.');
    }

    public function destroy(Job $job)
    {
        $job->delete();
        return redirect()->route('admin.freelance.jobs.index')->with('success', 'Job deleted successfully.');
    }
}
