<?php

namespace Modules\Freelance\Http\Controllers\Admin;

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

        $isSqlite = \DB::connection()->getDriverName() === 'sqlite';
        if ($isSqlite) {
            $query->orderByRaw("CASE status WHEN 'pending' THEN 1 WHEN 'approved' THEN 2 WHEN 'rejected' THEN 3 ELSE 4 END");
        } else {
            $query->orderByRaw("FIELD(status, 'pending', 'approved', 'rejected')");
        }

        $skills = $query->orderBy('name')
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

        return redirect()->route('admin.freelance.skills.index')->with('success', __('freelance.skill_created'));
    }

    public function bulkStore(Request $request)
    {
        $validated = $request->validate([
            'skills' => 'required|string'
        ]);

        $skillsText = trim($validated['skills']);
        $skillsArray = array_filter(array_map('trim', explode("\n", $skillsText)));

        $addedCount = 0;
        foreach ($skillsArray as $skillName) {
            if ($skillName !== '' && mb_strlen($skillName) <= 255) {
                if (!Skill::where('name', $skillName)->exists()) {
                    Skill::create([
                        'name' => $skillName,
                        'status' => 'approved',
                        'created_by' => auth()->id()
                    ]);
                    $addedCount++;
                }
            }
        }

        return redirect()->route('admin.freelance.skills.index')->with('success', __('freelance.bulk_skills_created', ['count' => $addedCount]));
    }

    public function update(Request $request, Skill $skill)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:freelance_skills,name,' . $skill->id,
            'description' => 'nullable|string'
        ]);

        $skill->update($validated);

        return redirect()->route('admin.freelance.skills.index')->with('success', __('freelance.skill_updated'));
    }

    public function approve(Skill $skill)
    {
        $skill->update(['status' => 'approved']);
        return redirect()->route('admin.freelance.skills.index')->with('success', __('freelance.skill_approved'));
    }

    public function reject(Skill $skill)
    {
        $skill->update(['status' => 'rejected']);
        return redirect()->route('admin.freelance.skills.index')->with('success', __('freelance.skill_rejected'));
    }

    public function blockUser(\App\Models\User $user)
    {
        $user->update(['can_add_freelance_skills' => false]);
        return redirect()->route('admin.freelance.skills.index')->with('success', __('freelance.user_blocked_skills', ['name' => $user->name]));
    }

    public function destroy(Skill $skill)
    {
        $skill->delete();
        return redirect()->route('admin.freelance.skills.index')->with('success', __('freelance.skill_deleted'));
    }
}
