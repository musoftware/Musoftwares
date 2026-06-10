<?php

namespace Modules\Freelance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Freelance\Models\FreelanceProfile;
use Modules\Freelance\Models\Skill;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        $user = $request->user();
        
        $profile = FreelanceProfile::firstOrCreate(
            ['user_id' => $user->id],
            [
                'title' => '',
                'bio' => '',
                'hourly_rate' => 0,
                'receive_job_notifications' => true,
            ]
        );

        $userSkills = $user->freelanceSkills()->withPivot('id')->get();
        $availableSkills = Skill::where('status', 'approved')->get();

        $profile->append(['average_rating', 'reviews_count']);

        $reviews = \Modules\Freelance\Models\Review::where('reviewee_id', $user->id)
            ->where('is_visible', true)
            ->with('reviewer:id,name')
            ->latest()
            ->get();

        return Inertia::render('Freelance/Profile/Edit', [
            'profile' => $profile,
            'userSkills' => $userSkills,
            'availableSkills' => $availableSkills,
            'reviews' => $reviews,
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'bio' => 'nullable|string',
            'hourly_rate' => 'nullable|numeric|min:0',
        ]);

        $user = $request->user();
        
        $profile = FreelanceProfile::firstOrCreate(['user_id' => $user->id]);
        $profile->update($validated);

        return redirect()->back()->with('success', __('general.saved_successfully'));
    }
}
