<?php

namespace Modules\Freelance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\User;
use Modules\Freelance\Models\FreelanceProfile;
use App\Traits\ConvertsCurrency;

class PublicFreelancerController extends Controller
{
    use ConvertsCurrency;

    public function index(Request $request)
    {
        $query = User::with(['freelanceProfile'])
            ->whereHas('freelanceProfile');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhereHas('freelanceProfile', function ($sq) use ($search) {
                      $sq->where('title', 'like', "%{$search}%")
                         ->orWhere('bio', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('rate_min') && is_numeric($request->rate_min)) {
            $query->whereHas('freelanceProfile', function ($q) use ($request) {
                $q->where('hourly_rate', '>=', $request->rate_min);
            });
        }

        if ($request->filled('rate_max') && is_numeric($request->rate_max)) {
            $query->whereHas('freelanceProfile', function ($q) use ($request) {
                $q->where('hourly_rate', '<=', $request->rate_max);
            });
        }

        $sort = $request->input('sort', 'newest');
        if ($sort === 'rate_high') {
            $query->orderBy(FreelanceProfile::select('hourly_rate')
                ->whereColumn('freelance_profiles.user_id', 'users.id'), 'desc');
        } elseif ($sort === 'rate_low') {
            $query->orderBy(FreelanceProfile::select('hourly_rate')
                ->whereColumn('freelance_profiles.user_id', 'users.id'), 'asc');
        } else {
            $query->latest();
        }

        $freelancers = $query->paginate(15)->withQueryString();

        $freelancerIds = $freelancers->pluck('id');
        $userSkills = \Modules\Freelance\Models\UserSkill::with('skill')
            ->whereIn('user_id', $freelancerIds)
            ->get()
            ->groupBy('user_id');

        $freelancers->getCollection()->transform(function ($user) use ($userSkills) {
            $user->freelance_skills = $userSkills->get($user->id, collect())->pluck('skill');
            // We append the accessors from the profile
            if ($user->freelanceProfile) {
                $user->freelanceProfile->setAppends(['average_rating', 'reviews_count']);
            }
            return $user;
        });

        $userCurrencyId = null;
        if ($request->user()) {
            try {
                $userCurrencyId = $this->getUserCurrencyObject($request->user())->id;
            } catch (\Exception $e) {}
        }
        
        $frontendCurrency = $userCurrencyId ? $this->currencyForFrontend($userCurrencyId) : null;

        return Inertia::render('Freelance/Freelancers/Browse', [
            'freelancers' => $freelancers,
            'userCurrency' => $frontendCurrency
        ]);
    }
}
