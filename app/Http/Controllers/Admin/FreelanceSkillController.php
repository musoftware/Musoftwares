<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Freelance\Models\Skill;
use Inertia\Inertia;

class FreelanceSkillController extends Controller
{
    public function index(Request $request)
    {
        $query = Skill::with('creator');

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
        }

        $skills = $query->orderByRaw("FIELD(status, 'pending', 'approved', 'rejected')")
                        ->orderBy('name')
                        ->paginate(20)
                        ->withQueryString();

        return Inertia::render('Admin/Freelance/Skills/Index', [
            'skills' => $skills,
            'filters' => $request->only('search')
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:freelance_skills,name',
            'description' => 'nullable|string'
        ]);

        Skill::create($validated);

        return redirect()->route('admin.freelance.skills.index')->with('success', __('freelance.skill_created', [], 'en'));
    }

    public function update(Request $request, Skill $skill)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:freelance_skills,name,' . $skill->id,
            'description' => 'nullable|string'
        ]);

        $skill->update($validated);

        return redirect()->route('admin.freelance.skills.index')->with('success', __('freelance.skill_updated', [], 'en'));
    }

    public function approve(Skill $skill)
    {
        $skill->update(['status' => 'approved']);
        return redirect()->route('admin.freelance.skills.index')->with('success', __('freelance.skill_approved', [], 'en'));
    }

    public function reject(Skill $skill)
    {
        $skill->update(['status' => 'rejected']);
        return redirect()->route('admin.freelance.skills.index')->with('success', __('freelance.skill_rejected', [], 'en'));
    }

    public function blockUser(\App\Models\User $user)
    {
        $user->update(['can_add_freelance_skills' => false]);
        return redirect()->route('admin.freelance.skills.index')->with('success', __('freelance.user_blocked_skills', ['name' => $user->name], 'en'));
    }

    public function destroy(Skill $skill)
    {
        $skill->delete();
        return redirect()->route('admin.freelance.skills.index')->with('success', __('freelance.skill_deleted', [], 'en'));
    }
}
