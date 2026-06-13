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
use App\Traits\ConvertsCurrency;

class FreelanceJobController extends Controller
{
    use ConvertsCurrency;
    public function __construct(private PostJobAction $postJobAction) {}

    public function index(Request $request)
    {
        $query = Job::with(['client', 'skills', 'currency'])->where('status', 'open');

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

        if ($request->filled('budget_min') && is_numeric($request->budget_min)) {
            $query->where('budget', '>=', $request->budget_min);
        }
        
        if ($request->filled('budget_max') && is_numeric($request->budget_max)) {
            $query->where('budget', '<=', $request->budget_max);
        }

        $sort = $request->input('sort', 'newest');
        $user = $request->user();

        if ($user) {
            $userSkillIds = \Modules\Freelance\Models\UserSkill::where('user_id', $user->id)->pluck('skill_id')->toArray();
            if (!empty($userSkillIds)) {
                $query->withCount(['skills as skill_match_count' => function ($q) use ($userSkillIds) {
                    $q->whereIn('freelance_skills.id', $userSkillIds);
                }]);
            }
        }

        if ($sort === 'budget_high') {
            $query->orderBy('budget', 'desc');
        } elseif ($sort === 'budget_low') {
            $query->orderBy('budget', 'asc');
        } else {
            // Newest with skill matching priority
            if ($user && !empty($userSkillIds)) {
                $query->orderBy('skill_match_count', 'desc');
            }
            $query->latest();
        }

        $jobs = $query->paginate(15)->withQueryString();
        
        $userCurrencyId = null;
        if ($user) {
            try {
                $userCurrencyId = $this->getUserCurrencyObject($user)->id;
            } catch (\Exception $e) {
                // If currency is misconfigured for auth user, fallback to null (original job currency)
            }
        }
        
        if ($userCurrencyId) {
            $jobs->through(function ($job) use ($userCurrencyId) {
                return $this->convertModelCurrency($job, 'budget', 'currency_id', $userCurrencyId);
            });
            $frontendCurrency = $this->currencyForFrontend($userCurrencyId);
        } else {
            $frontendCurrency = null;
        }

        return Inertia::render('Freelance/Jobs/Browse', [
            'jobs' => $jobs,
            'userCurrency' => $frontendCurrency
        ]);
    }

    public function myJobs(Request $request)
    {
        $jobs = Job::withCount('proposals')
            ->with('currency')
            ->where('client_id', $request->user()->id)
            ->latest()
            ->paginate(15);

        $userCurrencyModel = $this->getUserCurrencyObject($request->user());
        $userCurrencyId = $userCurrencyModel->id;

        $jobs->through(function ($job) use ($userCurrencyId) {
            return $this->convertModelCurrency($job, 'budget', 'currency_id', $userCurrencyId);
        });

        return Inertia::render('Freelance/Jobs/MyJobs', [
            'jobs' => $jobs,
            'userCurrency' => $this->currencyForFrontend($userCurrencyId)
        ]);
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
            'service_type' => 'required|in:visit,remote',
            'country' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255',
            'duration' => 'nullable|string|max:255',
            'skills' => 'required|array',
            'skills.*' => 'required',
        ]);

        $user = $request->user();

        // Process dynamic skills
        $processedSkills = [];
        $newSkillsCount = 0;

        foreach ($validated['skills'] as $skill) {
            // Frontend sends skills as objects: {id, name, required}
            // Normalize to either an int ID or a string name
            if (is_array($skill)) {
                $skillId   = $skill['id'] ?? null;
                $skillName = isset($skill['name']) ? trim($skill['name']) : null;
            } elseif (is_numeric($skill)) {
                $skillId   = (int) $skill;
                $skillName = null;
            } else {
                $skillId   = null;
                $skillName = trim((string) $skill);
            }

            // Case 1: We have a numeric ID — use it directly
            if ($skillId && is_numeric($skillId)) {
                $existingSkill = \Modules\Freelance\Models\Skill::find((int) $skillId);
                if ($existingSkill && $existingSkill->status === 'rejected') {
                    return back()->withErrors(['skills' => __('freelance.skill_rejected', ['skill' => $existingSkill->name])]);
                }
                $processedSkills[] = (int) $skillId;
                continue;
            }

            // Case 2: We have a name — look it up or create it
            if ($skillName) {
                $existingSkill = \Modules\Freelance\Models\Skill::whereRaw('LOWER(name) = ?', [strtolower($skillName)])->first();

                if ($existingSkill) {
                    if ($existingSkill->status === 'rejected') {
                        return back()->withErrors(['skills' => __('freelance.skill_rejected', ['skill' => $skillName])]);
                    }
                    $processedSkills[] = $existingSkill->id;
                } else {
                    if (!$user->can_add_freelance_skills) {
                        return back()->withErrors(['skills' => __('freelance.skills_not_allowed')]);
                    }

                    if ($newSkillsCount == 0) {
                        $userAddedCount = \Modules\Freelance\Models\Skill::where('created_by', $user->id)->count();
                        if ($userAddedCount >= 3) {
                            return back()->withErrors(['skills' => __('freelance.skills_limit_exceeded')]);
                        }
                    }

                    $newSkillsCount++;
                    if ($newSkillsCount > 3) {
                        return back()->withErrors(['skills' => __('freelance.skills_limit_exceeded')]);
                    }

                    $newSkill = \Modules\Freelance\Models\Skill::create([
                        'name'       => $skillName,
                        'status'     => 'pending',
                        'created_by' => $user->id,
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
            serviceType: $validated['service_type'] ?? 'remote',
            country: $validated['country'] ?? null,
            city: $validated['city'] ?? null,
            district: $validated['district'] ?? null,
            latitude: $request->input('latitude'),
            longitude: $request->input('longitude'),
            duration: $validated['duration'],
            skills: $processedSkills,
        );

        try {
            $job = $this->postJobAction->execute($data, $user);
        } catch (\Exception $e) {
            return back()->withErrors(['points' => $e->getMessage()]);
        }

        return redirect()->route('freelance.my-jobs')->with('success', __('freelance.job_posted_successfully'));
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

    public function show(Request $request, Job $job, ?string $slug = null)
    {
        // Track unique views (per session) if viewer is not the job owner
        $user = $request->user();
        if (!$user || $user->id !== $job->client_id) {
            $viewedJobs = $request->session()->get('viewed_freelance_jobs', []);
            if (!in_array($job->id, $viewedJobs)) {
                $job->increment('views_count');
                $viewedJobs[] = $job->id;
                $request->session()->put('viewed_freelance_jobs', $viewedJobs);
            }
        }

        $job->load([
            'client', 
            'skills', 
            'proposals' => function ($q) {
                $q->orderBy('points_spent', 'desc')->latest();
            },
            'proposals.freelancer', 
            'currency'
        ]);
        
        $userCurrencyId = $user ? $this->getUserCurrencyObject($user)->id : $job->currency_id;
        
        $this->convertModelCurrency($job, 'budget', 'currency_id', $userCurrencyId);
        if ($job->relationLoaded('proposals')) {
            $job->proposals->transform(function ($proposal) use ($userCurrencyId) {
                $this->convertModelCurrency($proposal, 'bid_amount', 'currency_id', $userCurrencyId);
                return $proposal;
            });
        }

        return Inertia::render('Freelance/Jobs/Show', [
            'job' => $job,
            'pointsCost' => 2, // Example cost to submit a proposal
            'userCurrency' => $this->currencyForFrontend($userCurrencyId),
            'jobSlug' => \Illuminate\Support\Str::slug($job->title)
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
        return back()->with('success', __('general.job_updated_successfully'));
    }

    public function destroy(Request $request, Job $job)
    {
        Gate::authorize('delete', $job);

        app(\Modules\Freelance\Domains\Proposal\Actions\RejectPendingProposalsAction::class)
            ->execute($job, 'Job Deleted by Client');

        $job->status->transitionTo(\Modules\Freelance\Domains\Job\States\Cancelled::class);
        $job->delete();
        return redirect()->route('freelance.my-jobs')->with('success', __('general.job_deleted'));
    }

    public function poke(Request $request, Job $job)
    {
        $user = $request->user();
        if ($user->id !== $job->client_id && !$job->proposals()->where('freelancer_id', $user->id)->exists()) {
            abort(403);
        }

        if ((string) $job->status !== 'open') {
            return back()->with('error', __('freelance.job_must_be_open_to_poke'));
        }

        if ($job->last_poked_at && $job->last_poked_at->diffInHours(now()) < 24) {
            return back()->with('error', __('freelance.poke_too_soon'));
        }

        \Modules\Freelance\Jobs\PokeFreelancersForJob::dispatch($job);

        $job->update(['last_poked_at' => now()]);

        return back()->with('success', __('freelance.poke_success'));
    }
}
