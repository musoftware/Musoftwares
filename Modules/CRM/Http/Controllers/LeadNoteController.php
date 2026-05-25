<?php

namespace Modules\CRM\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\CRM\Models\Lead;
use Modules\CRM\Models\LeadNote;
use Illuminate\Http\Request;

class LeadNoteController extends Controller
{
    public function store(Request $request, Lead $lead)
    {
        $validated = $request->validate([
            'note' => 'required|string',
            'is_pinned' => 'boolean',
        ]);

        $authorId = session('erp_team_member_id') ?? auth()->id();
        $authorType = session('erp_team_member_id') ? 'Modules\ERP\Models\TeamMember' : 'App\Models\User';

        $lead->notes()->create([
            'user_id' => auth()->id(), // Tenant Owner
            'authorable_id' => $authorId,
            'authorable_type' => $authorType,
            'note' => $validated['note'],
            'is_pinned' => $validated['is_pinned'] ?? false,
        ]);

        return redirect()->back()->with('success', 'Note added successfully.');
    }

    public function update(Request $request, Lead $lead, LeadNote $note)
    {
        $validated = $request->validate([
            'note' => 'string',
            'is_pinned' => 'boolean',
        ]);

        $note->update($validated);

        return redirect()->back()->with('success', 'Note updated successfully.');
    }

    public function destroy(Lead $lead, LeadNote $note)
    {
        $note->delete();

        return redirect()->back()->with('success', 'Note deleted successfully.');
    }
}
