<?php

namespace Modules\CRM\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\CRM\Models\Campaign;
use Modules\CRM\Models\Lead;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class LeadCaptureController extends Controller
{
    /**
     * Render the embeddable Lead Capture form for a specific campaign.
     * The token uniquely identifies the campaign to track leads correctly.
     */
    public function show(Request $request, $token)
    {
        // For security, the token should map to a Campaign. 
        // We'll assume the token is the campaign UUID or a specific embed_token.
        $campaign = Campaign::where('embed_token', $token)->orWhere('id', $token)->firstOrFail();

        return Inertia::render('CRM/Embeds/LeadCaptureForm', [
            'campaign' => $campaign,
            'token' => $token,
        ]);
    }

    /**
     * Handle the form submission from the embedded iframe.
     */
    public function store(Request $request, $token)
    {
        $campaign = Campaign::where('embed_token', $token)->orWhere('id', $token)->firstOrFail();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:50',
            'message' => 'nullable|string',
        ]);

        try {
            $lead = new Lead();
            $lead->user_id = $campaign->user_id; // Scope to the tenant who owns the campaign
            $lead->campaign_id = $campaign->id;
            $lead->name = $validated['name'];
            $lead->email = $validated['email'];
            $lead->phone = $validated['phone'] ?? null;
            // $lead->message = $validated['message'] ?? null; // If message field exists on Lead model
            $lead->status = 'new';
            $lead->source = 'iframe_embed';
            $lead->save();

            return redirect()->back()->with('success', 'Thank you! Your information has been submitted.');
        } catch (\Exception $e) {
            Log::error('Lead capture failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Something went wrong. Please try again.');
        }
    }
}
