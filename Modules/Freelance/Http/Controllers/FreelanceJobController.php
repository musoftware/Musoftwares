<?php

namespace Modules\Freelance\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Freelance\Models\Job;
use App\Models\PointTransaction;
use Modules\Freelance\Jobs\NotifyFreelancersForJob;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class FreelanceJobController extends Controller
{
    public function index(Request $request)
    {
        $query = Job::with(['client', 'skills'])->where('status', 'open');

        if ($request->filled('skill_id')) {
            $query->whereHas('skills', function ($q) use ($request) {
                $q->where('freelance_skills.id', $request->skill_id);
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('skills', function ($sq) use ($search) {
                      $sq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('type') && in_array($request->type, ['fixed', 'hourly'])) {
            $query->where('type', $request->type);
        }

        $sort = $request->input('sort', 'newest');
        if ($sort === 'budget_high') {
            $query->orderBy('budget', 'desc');
        } elseif ($sort === 'budget_low') {
            $query->orderBy('budget', 'asc');
        } else {
            $query->latest();
        }

        $jobs = $query->paginate(15)->withQueryString();
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
            'currency_id' => 'required|integer|exists:currencies,id',
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
                'currency_id' => $validated['currency_id'],
                'type' => $validated['type'],
                'duration' => $validated['duration'],
                'status' => 'open',
            ]);

            $job->skills()->syncWithPivotValues($validated['skills'], ['is_required' => true]);

            $user->points_balance -= $postCost;
            $user->save();

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

    public function create(Request $request)
    {
        $user = $request->user();
        $preferredCurrency = $user->preferred_currency ?: 'USD';
        
        $financeService = app(\App\Services\FinanceService::class);
        $egpToPreferredRate = $financeService->getExchangeRate('EGP', $preferredCurrency);

        return Inertia::render('Freelance/Jobs/Create', [
            'egpToPreferredRate' => $egpToPreferredRate,
        ]);
    }

    public function show(Job $job)
    {
        $job->load(['client', 'skills', 'proposals.freelancer']);
        return Inertia::render('Freelance/Jobs/Show', [
            'job' => $job,
            'pointsCost' => 2 // Example cost to submit a proposal
        ]);
    }

    public function edit(Job $job)
    {
        if ($job->client_id !== auth()->id()) {
            abort(403);
        }

        $job->load('skills');
        return Inertia::render('Freelance/Jobs/Edit', ['job' => $job]);
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
