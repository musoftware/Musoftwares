<?php

namespace Modules\Freelance\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Freelance\Models\UserSkill;
use Illuminate\Http\Request;

class UserSkillController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'skill_id' => 'required|exists:freelance_skills,id',
        ]);

        $request->user()->freelanceSkills()->syncWithoutDetaching([$validated['skill_id']]);
        return back()->with('success', 'Skill added to your profile.');
    }

    public function destroy(Request $request, $skillId)
    {
        $request->user()->freelanceSkills()->detach($skillId);
        return back()->with('success', 'Skill removed from your profile.');
    }
}
