<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Marketing\PlatformSequence;
use App\Models\Marketing\PlatformSequenceStep;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Http;

class SequenceController extends Controller
{
    public function index()
    {
        $sequences = PlatformSequence::withCount(['steps', 'states'])->latest()->paginate(20);
        return Inertia::render('Admin/Sequences/Index', [
            'sequences' => $sequences
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'trigger_type' => 'required|string',
            'is_active' => 'boolean',
        ]);

        PlatformSequence::create($validated);
        return redirect()->back()->with('success', 'Sequence created successfully.');
    }

    public function show(PlatformSequence $sequence)
    {
        $sequence->load('steps');
        return Inertia::render('Admin/Sequences/Show', [
            'sequence' => $sequence
        ]);
    }

    public function destroy(PlatformSequence $sequence)
    {
        $sequence->delete();
        return redirect()->route('admin.sequences.index')->with('success', 'Sequence deleted.');
    }

    // -- Steps Management --
    public function storeStep(Request $request, PlatformSequence $sequence)
    {
        $validated = $request->validate([
            'order' => 'required|integer',
            'delay' => 'required|integer',
            'unit' => 'required|in:minute,hour,day',
            'send_email' => 'boolean',
            'send_whatsapp' => 'boolean',
            'email_subject_en' => 'nullable|string',
            'email_content_en' => 'nullable|string',
            'whatsapp_content_en' => 'nullable|string',
        ]);

        $sequence->steps()->create([
            'order' => $validated['order'],
            'delay' => $validated['delay'],
            'unit' => $validated['unit'],
            'send_email' => $validated['send_email'] ?? false,
            'send_whatsapp' => $validated['send_whatsapp'] ?? false,
            'email_subject' => ['en' => $validated['email_subject_en'] ?? ''],
            'email_content' => ['en' => $validated['email_content_en'] ?? ''],
            'whatsapp_content' => ['en' => $validated['whatsapp_content_en'] ?? ''],
        ]);

        return redirect()->back()->with('success', 'Step added.');
    }

    public function updateStep(Request $request, PlatformSequenceStep $step)
    {
        $validated = $request->validate([
            'order' => 'required|integer',
            'delay' => 'required|integer',
            'unit' => 'required|in:minute,hour,day',
            'send_email' => 'boolean',
            'send_whatsapp' => 'boolean',
            'email_subject_en' => 'nullable|string',
            'email_content_en' => 'nullable|string',
            'whatsapp_content_en' => 'nullable|string',
        ]);

        $step->update([
            'order' => $validated['order'],
            'delay' => $validated['delay'],
            'unit' => $validated['unit'],
            'send_email' => $validated['send_email'] ?? false,
            'send_whatsapp' => $validated['send_whatsapp'] ?? false,
            'email_subject' => ['en' => $validated['email_subject_en'] ?? ''],
            'email_content' => ['en' => $validated['email_content_en'] ?? ''],
            'whatsapp_content' => ['en' => $validated['whatsapp_content_en'] ?? ''],
        ]);

        return redirect()->back()->with('success', 'Step updated.');
    }

    public function deleteStep(PlatformSequenceStep $step)
    {
        $step->delete();
        return redirect()->back()->with('success', 'Step deleted.');
    }

    // -- AI Generation --
    public function generateStepsWithAI(Request $request, PlatformSequence $sequence)
    {
        $request->validate([
            'context' => 'nullable|string',
            'num_steps' => 'required|integer|min:1|max:5',
            'tone' => 'required|string',
        ]);

        $apiKey = config('services.openai.key') ?? env('OPENAI_API_KEY');
        if (empty($apiKey)) {
            return response()->json(['error' => 'OpenAI API key missing'], 400);
        }

        $prompt = "You are a marketing automation expert. Generate {$request->num_steps} sequence steps for a drip campaign.
Context: {$request->context}
Tone: {$request->tone}

Return ONLY valid JSON array with this structure:
[
  {
    \"order\": 1,
    \"delay\": 1,
    \"unit\": \"day\",
    \"send_email\": true,
    \"send_whatsapp\": false,
    \"email_subject_en\": \"...\",
    \"email_content_en\": \"...\",
    \"whatsapp_content_en\": \"...\"
  }
]";

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
            
            $steps = json_decode(trim($content), true);
            
            if (!$steps) {
                throw new \Exception("Invalid JSON from AI.");
            }

            return response()->json(['steps' => $steps]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function applyGeneratedSteps(Request $request, PlatformSequence $sequence)
    {
        $request->validate([
            'steps' => 'required|array'
        ]);

        $currentMaxOrder = $sequence->steps()->max('order') ?? 0;

        foreach ($request->input('steps') as $step) {
            $sequence->steps()->create([
                'order' => $currentMaxOrder + ($step['order'] ?? 1),
                'delay' => $step['delay'] ?? 1,
                'unit' => $step['unit'] ?? 'day',
                'send_email' => $step['send_email'] ?? false,
                'send_whatsapp' => $step['send_whatsapp'] ?? false,
                'email_subject' => ['en' => $step['email_subject_en'] ?? ''],
                'email_content' => ['en' => $step['email_content_en'] ?? ''],
                'whatsapp_content' => ['en' => $step['whatsapp_content_en'] ?? ''],
            ]);
            $currentMaxOrder++;
        }

        return redirect()->back()->with('success', 'AI steps applied successfully.');
    }
}
