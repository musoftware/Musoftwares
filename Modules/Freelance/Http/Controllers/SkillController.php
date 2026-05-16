<?php

namespace Modules\Freelance\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Freelance\Models\Skill;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SkillController extends Controller
{
    public function index()
    {
        $skills = Skill::orderBy('name')->get();
        return response()->json($skills);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:freelance_skills',
            'description' => 'nullable|string',
        ]);

        Skill::create($validated);
        return back()->with('success', 'Skill created.');
    }

    public function update(Request $request, Skill $skill)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:freelance_skills,name,' . $skill->id,
            'description' => 'nullable|string',
        ]);

        $skill->update($validated);
        return back()->with('success', 'Skill updated.');
    }

    public function destroy(Skill $skill)
    {
        $skill->delete();
        return back()->with('success', 'Skill deleted.');
    }
}
