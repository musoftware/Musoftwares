<?php

namespace Modules\Freelance\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Freelance\Models\Job;
use Modules\Freelance\Models\PointTransaction;
use Modules\Freelance\Jobs\NotifyFreelancersForJob;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class FreelanceJobController extends Controller
{
    public function index(Request $request)
    {
        $query = Job::with(['client', 'skills'])->where('status', 'open');

        if ($request->has('skill_id')) {
            $query->whereHas('skills', function ($q) use ($request) {
                $q->where('freelance_skills.id', $request->skill_id);
            });
        }

        $jobs = $query->latest()->paginate(15);
        return Inertia::render('Freelance/Jobs/Browse', ['jobs' => $jobs]);
    }

    public function myJobs(Request $request)
    {
        $jobs = Job::withCount('proposals')
            ->where('client_id', $request->user()->id)
            ->latest()
            ->paginate(15);

        return Inertia::render('Freelance/Jobs/MyJobs', ['jobs' => $jobs]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'budget' => 'required|numeric|min:0',
            'currency_code' => 'required|string|size:3',
            'type' => 'required|in:fixed,hourly',
            'duration' => 'nullable|string|max:255',
            'skills' => 'required|array',
            'skills.*' => 'exists:freelance_skills,id',
        ]);

        $postCost = 10; // Cost to post a job
        $user = $request->user();

        if ($user->points_balance < $postCost) {
            return back()->withErrors(['points' => 'Insufficient points to post a job.']);
        }

        $job = DB::transaction(function () use ($validated, $user, $postCost) {
            $job = Job::create([
                'client_id' => $user->id,
                'title' => $validated['title'],
                'description' => $validated['description'],
                'budget' => $validated['budget'],
                'currency_code' => $validated['currency_code'],
                'type' => $validated['type'],
                'duration' => $validated['duration'],
                'status' => 'open',
            ]);

            $job->skills()->syncWithPivotValues($validated['skills'], ['is_required' => true]);

            PointTransaction::create([
                'user_id' => $user->id,
                'points' => $postCost,
                'type' => 'spent',
                'description' => "Posted job: {$job->title}",
            ]);

            return $job;
        });

        NotifyFreelancersForJob::dispatch($job);

        return redirect()->route('freelance.my-jobs')->with('success', 'Job posted successfully.');
    }

    public function show(Job $job)
    {
        $job->load(['client', 'skills']);
        return Inertia::render('Freelance/Jobs/Show', [
            'job' => $job,
            'pointsCost' => 2 // Example cost to submit a proposal
        ]);
    }

    public function update(Request $request, Job $job)
    {
        if ($job->client_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'budget' => 'required|numeric|min:0',
            'type' => 'required|in:fixed,hourly',
            'duration' => 'nullable|string|max:255',
        ]);

        $job->update($validated);
        return back()->with('success', 'Job updated successfully.');
    }

    public function destroy(Request $request, Job $job)
    {
        if ($job->client_id !== $request->user()->id) {
            abort(403);
        }

        $job->delete();
        return redirect()->route('freelance.my-jobs')->with('success', 'Job deleted.');
    }
}
