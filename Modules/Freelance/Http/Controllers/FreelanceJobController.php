<?php

namespace Modules\Freelance\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Freelance\Models\Job;
use App\Models\PointTransaction;
use Modules\Freelance\Jobs\NotifyFreelancersForJob;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Modules\Freelance\Domains\Job\Actions\PostJobAction;
use Modules\Freelance\Domains\Job\DTOs\PostJobData;
use Illuminate\Support\Facades\Gate;

class FreelanceJobController extends Controller
{
    public function __construct(private PostJobAction $postJobAction) {}

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

        $data = new PostJobData(
            clientId: $user->id,
            title: $validated['title'],
            description: $validated['description'],
            budget: $validated['budget'],
            currencyId: $validated['currency_id'],
            type: $validated['type'],
            duration: $validated['duration'],
            skills: $validated['skills'],
        );

        try {
            $job = $this->postJobAction->execute($data, $user, $postCost);
        } catch (\Exception $e) {
            return back()->withErrors(['points' => $e->getMessage()]);
        }

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
        Gate::authorize('update', $job);

        $job->load('skills');
        return Inertia::render('Freelance/Jobs/Edit', ['job' => $job]);
    }

    public function update(Request $request, Job $job)
    {
        Gate::authorize('update', $job);

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
        Gate::authorize('delete', $job);

        $job->delete();
        return redirect()->route('freelance.my-jobs')->with('success', 'Job deleted.');
    }
}
