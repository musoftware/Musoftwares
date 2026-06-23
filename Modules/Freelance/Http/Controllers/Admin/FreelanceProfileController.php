<?php

namespace Modules\Freelance\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Freelance\Models\FreelanceProfile;
use Modules\Freelance\Models\UserSkill;
use Modules\Freelance\Models\Skill;
use Inertia\Inertia;

class FreelanceProfileController extends Controller
{
    public function index(Request $request)
    {
        // Only get profiles of users who have added at least one skill
        $query = FreelanceProfile::with(['user', 'user.skills.skill'])
            ->whereHas('user.skills');

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->whereHas('user', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            })->orWhere('title', 'like', "%{$search}%");
        }

        $profiles = $query->paginate(20)->withQueryString();

        return Inertia::render('Admin/Freelance/Profiles/Index', [
            'profiles' => $profiles,
            'filters' => $request->only('search')
        ]);
    }

    public function edit(FreelanceProfile $profile)
    {
        $profile->load(['user', 'user.skills.skill']);
        $availableSkills = Skill::where('status', 'approved')->orderBy('name')->get();

        return Inertia::render('Admin/Freelance/Profiles/Edit', [
            'profile' => $profile,
            'availableSkills' => $availableSkills
        ]);
    }

    public function update(Request $request, FreelanceProfile $profile)
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'bio' => 'nullable|string',
            'skills' => 'array',
            'skills.*' => 'exists:freelance_skills,id'
        ]);

        $profile->update([
            'title' => $validated['title'],
            'bio' => $validated['bio'],
        ]);

        if (isset($validated['skills'])) {
            // Get current user skills
            $currentUserSkillIds = UserSkill::where('user_id', $profile->user_id)->pluck('skill_id')->toArray();
            
            // Skills to add
            $skillsToAdd = array_diff($validated['skills'], $currentUserSkillIds);
            foreach ($skillsToAdd as $skillId) {
                UserSkill::create([
                    'user_id' => $profile->user_id,
                    'skill_id' => $skillId
                ]);
            }

            // Skills to remove
            $skillsToRemove = array_diff($currentUserSkillIds, $validated['skills']);
            if (!empty($skillsToRemove)) {
                UserSkill::where('user_id', $profile->user_id)
                    ->whereIn('skill_id', $skillsToRemove)
                    ->delete();
            }
        }

        return redirect()->route('admin.freelance.profiles.index')
            ->with('success', __('admin.profile_updated_successfully'));
    }

    public function destroy(FreelanceProfile $profile)
    {
        // Optional: you may want to delete the UserSkill entries here or let DB cascade
        UserSkill::where('user_id', $profile->user_id)->delete();
        $profile->delete();

        return redirect()->route('admin.freelance.profiles.index')
            ->with('success', __('admin.profile_deleted_successfully'));
    }
}
