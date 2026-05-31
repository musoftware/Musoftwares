<?php

namespace Modules\CRM\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\CRM\Models\Lead;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\CRM\Models\LeadTag;

class LeadTagController extends Controller
{
    public function index()
    {
        $tags = \Modules\CRM\Models\LeadTag::orderBy('created_at', 'desc')->get();
        return Inertia::render('CRM/Tags/Index', [
            'tags' => $tags
        ]);
    }

    public function apiIndex()
    {
        return response()->json(\Modules\CRM\Models\LeadTag::all());
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

        return redirect()->back()->with('success', __('crm.tag_added'));
    }

    public function detach(Request $request, Lead $lead, LeadTag $tag)
    {
        $lead->tags()->detach($tag->id);

        return redirect()->back()->with('success', __('crm.tag_removed'));
    }
}
