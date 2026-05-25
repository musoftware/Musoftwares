<?php

namespace Modules\CRM\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\CRM\Models\LeadTag;
use Modules\CRM\Models\Lead;
use Illuminate\Http\Request;

class LeadTagController extends Controller
{
    public function index()
    {
        return response()->json(LeadTag::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'color' => 'nullable|string|max:20',
        ]);

        // BelongsToTenant will auto-assign user_id
        $tag = LeadTag::create($validated);

        return response()->json($tag);
    }

    public function destroy(LeadTag $tag)
    {
        $tag->delete();
        return response()->json(['success' => true]);
    }

    // Attach/Detach from Lead
    public function attach(Request $request, Lead $lead)
    {
        $validated = $request->validate([
            'tag_id' => 'required|exists:lead_tags,id',
        ]);

        $lead->tags()->syncWithoutDetaching([$validated['tag_id']]);

        return redirect()->back()->with('success', 'Tag added to lead.');
    }

    public function detach(Request $request, Lead $lead, LeadTag $tag)
    {
        $lead->tags()->detach($tag->id);

        return redirect()->back()->with('success', 'Tag removed from lead.');
    }
}
