<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CRM\PlatformLead;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LeadController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'all');

        $query = PlatformLead::query();

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $leads = $query->latest()->paginate(20);

        return Inertia::render('Admin/Leads/Index', [
            'leads' => $leads,
            'currentTab' => $status,
        ]);
    }

    public function updateStatus(Request $request, PlatformLead $lead)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:new,contacted,converted,dead',
        ]);

        $lead->status = $validated['status'];
        $lead->save();

        return redirect()->back()->with('success', 'Lead status updated.');
    }

    public function destroy(PlatformLead $lead)
    {
        $lead->delete();

        return redirect()->back()->with('success', 'Lead deleted successfully.');
    }
}
