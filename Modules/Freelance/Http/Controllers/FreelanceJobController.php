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
            'min_proposal_points' => 'required|integer|min:0',
            'type' => 'required|in:fixed,hourly',
            'duration' => 'nullable|string|max:255',
            'skills' => 'required|array',
            'skills.*' => 'required',
        ]);

        $user = $request->user();

        // Process dynamic skills
        $processedSkills = [];
        $newSkillsCount = 0;

        foreach ($validated['skills'] as $skill) {
            if (is_numeric($skill)) {
                $processedSkills[] = (int) $skill;
            } else {
                $skillName = trim($skill);
                $existingSkill = \Modules\Freelance\Models\Skill::whereRaw('LOWER(name) = ?', [strtolower($skillName)])->first();

                if ($existingSkill) {
                    if ($existingSkill->status === 'rejected') {
                        return back()->withErrors(['skills' => "The skill '{$skillName}' has been rejected by the admin."]);
                    }
                    $processedSkills[] = $existingSkill->id;
                } else {
                    if (!$user->can_add_freelance_skills) {
                        return back()->withErrors(['skills' => 'You are not allowed to add new skills.']);
                    }

                    if ($newSkillsCount == 0) {
                        $userAddedCount = \Modules\Freelance\Models\Skill::where('created_by', $user->id)->count();
                        if ($userAddedCount >= 3) {
                            return back()->withErrors(['skills' => 'You can only add up to 3 new skills to the system.']);
                        }
                    }

                    $newSkillsCount++;
                    if ($newSkillsCount > 3) {
                        return back()->withErrors(['skills' => 'You can only add up to 3 new skills to the system.']);
                    }

                    $newSkill = \Modules\Freelance\Models\Skill::create([
                        'name' => $skillName,
                        'status' => 'pending',
                        'created_by' => $user->id
                    ]);
                    $processedSkills[] = $newSkill->id;
                }
            }
        }

        $data = new PostJobData(
            clientId: $user->id,
            title: $validated['title'],
            description: $validated['description'],
            budget: $validated['budget'],
            currencyId: $validated['currency_id'],
            minProposalPoints: $validated['min_proposal_points'],
            type: $validated['type'],
            duration: $validated['duration'],
            skills: $processedSkills,
        );

        try {
            $job = $this->postJobAction->execute($data, $user);
        } catch (\Exception $e) {
            return back()->withErrors(['points' => $e->getMessage()]);
        }

        return redirect()->route('freelance.my-jobs')->with('success', __('Job posted successfully.'));
    }

    public function create(Request $request)
    {
        $user = $request->user();

        $currencies = \App\Models\Currency::orderBy('currency')->get(['id', 'currency', 'symbol', 'string_format']);

        $egpCurrency = $currencies->firstWhere('currency', 'EGP');
        $userCurrency = $currencies->firstWhere('id', $user->currency_id);
        $preferredCurrencyCode = $userCurrency?->currency;

        if (!$egpCurrency || !$userCurrency || !$preferredCurrencyCode) {
            throw new \Exception(__('errors.currency_configuration_missing'));
        }

        $egpToPreferredRate = \App\Models\CurrenciesExchange::RateByDate(
            now(),
            1,
            $egpCurrency->id,
            $userCurrency->id
        );

        return Inertia::render('Freelance/Jobs/Create', [
            'currencies' => $currencies,
            'egpToPreferredRate' => $egpToPreferredRate,
            'preferredCurrency' => $preferredCurrencyCode,
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
