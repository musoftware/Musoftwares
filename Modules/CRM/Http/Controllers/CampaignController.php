<?php

namespace Modules\CRM\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\CRM\Models\Campaign;
use Illuminate\Support\Str;

class CampaignController extends Controller
{
    public function index(Request $request)
    {
        $campaigns = Campaign::where('user_id', $request->user()->id)
            ->withCount('leads')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return Inertia::render('CRM/Campaigns/Index', [
            'campaigns' => $campaigns
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'form_title' => 'nullable|string|max:255',
            'form_description' => 'nullable|string',
            'button_text' => 'nullable|string|max:50',
        ]);

        $campaign = new Campaign();
        $campaign->user_id = $request->user()->id;
        $campaign->embed_token = (string) Str::uuid();
        $campaign->name = $validated['name'];
        $campaign->description = $validated['description'];
        $campaign->form_title = $validated['form_title'];
        $campaign->form_description = $validated['form_description'];
        $campaign->button_text = $validated['button_text'];
        $campaign->status = 'active';
        $campaign->save();

        return redirect()->back()->with('success', 'Campaign created successfully.');
    }

    public function show(Request $request, Campaign $campaign)
    {
        if ($campaign->user_id !== $request->user()->id) {
            abort(403);
        }

        $campaign->load('leads');

        return Inertia::render('CRM/Campaigns/Show', [
            'campaign' => $campaign
        ]);
    }
}
