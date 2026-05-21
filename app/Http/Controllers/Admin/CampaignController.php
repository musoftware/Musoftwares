<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Marketing\PlatformCampaign;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Http;

class CampaignController extends Controller
{
    public function index()
    {
        $campaigns = PlatformCampaign::withCount('recipients')->latest()->paginate(20);
        return Inertia::render('Admin/Campaigns/Index', [
            'campaigns' => $campaigns
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:email,whatsapp,mixed',
            'target_audience' => 'required|string',
        ]);

        $campaign = PlatformCampaign::create($validated);
        
        return redirect()->route('admin.campaigns.show', $campaign->id)->with('success', 'Campaign created successfully.');
    }

    public function show(PlatformCampaign $campaign)
    {
        $campaign->loadCount('recipients');
        return Inertia::render('Admin/Campaigns/Show', [
            'campaign' => $campaign
        ]);
    }

    public function update(Request $request, PlatformCampaign $campaign)
    {
        $validated = $request->validate([
            'email_subject_en' => 'nullable|string',
            'email_content_en' => 'nullable|string',
            'whatsapp_content_en' => 'nullable|string',
        ]);

        $campaign->update([
            'email_subject' => ['en' => $validated['email_subject_en'] ?? ''],
            'email_content' => ['en' => $validated['email_content_en'] ?? ''],
            'whatsapp_content' => ['en' => $validated['whatsapp_content_en'] ?? ''],
        ]);

        return redirect()->back()->with('success', 'Campaign content saved.');
    }

    public function destroy(PlatformCampaign $campaign)
    {
        $campaign->delete();
        return redirect()->route('admin.campaigns.index')->with('success', 'Campaign deleted.');
    }

    // -- AI Generation --
    public function generateAIContent(Request $request)
    {
        $request->validate([
            'context' => 'required|string',
            'tone' => 'required|string',
            'type' => 'required|in:email,whatsapp,mixed'
        ]);

        $apiKey = config('services.openai.key') ?? env('OPENAI_API_KEY');
        if (empty($apiKey)) {
            return response()->json(['error' => 'OpenAI API key missing'], 400);
        }

        $typeInstruction = match ($request->type) {
            'email' => 'an engaging Email Subject and Email Body',
            'whatsapp' => 'a short, punchy WhatsApp broadcast message',
            'mixed' => 'an engaging Email Subject, Email Body, AND a short WhatsApp broadcast message'
        };

        $prompt = "You are an expert copywriter. Write {$typeInstruction} based on this context.
Context: {$request->context}
Tone: {$request->tone}

Return ONLY valid JSON with this structure:
{
  \"email_subject_en\": \"...\",
  \"email_content_en\": \"...\",
  \"whatsapp_content_en\": \"...\"
}";

        try {
            $response = Http::timeout(60)->withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->post("https://api.openai.com/v1/chat/completions", [
                "model" => "gpt-4o-mini",
                "messages" => [
                    ["role" => "system", "content" => "Return raw JSON only."],
                    ["role" => "user", "content" => $prompt]
                ]
            ]);

            $content = $response->json('choices.0.message.content') ?? '';
            $content = preg_replace('/```json\s*/', '', $content);
            $content = preg_replace('/```\s*/', '', $content);
            
            $generated = json_decode(trim($content), true);
            
            if (!$generated) {
                throw new \Exception("Invalid JSON from AI.");
            }

            return response()->json(['content' => $generated]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // -- Status Management --
    public function schedule(Request $request, PlatformCampaign $campaign)
    {
        $campaign->update([
            'status' => 'scheduled',
            'scheduled_at' => now()->addMinutes(5) // In a real system, take from input
        ]);
        return redirect()->back()->with('success', 'Campaign scheduled.');
    }

    public function pause(PlatformCampaign $campaign)
    {
        $campaign->update(['status' => 'paused']);
        return redirect()->back()->with('success', 'Campaign paused.');
    }

    public function resume(PlatformCampaign $campaign)
    {
        $campaign->update(['status' => 'sending']);
        return redirect()->back()->with('success', 'Campaign resumed.');
    }
}
