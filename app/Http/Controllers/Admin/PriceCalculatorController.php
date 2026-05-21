<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Billing\PlatformContract;
use App\Models\Billing\PlatformProposal;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;

class PriceCalculatorController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Calculator/Index', [
            // Could load previous proposals here if needed
            'proposals' => PlatformProposal::latest()->take(10)->get()
        ]);
    }

    public function calculateAI(Request $request)
    {
        $request->validate([
            'project_details' => 'required|string|min:20',
        ]);

        $projectDetails = $request->input('project_details');
        
        // Use environment OpenAI key or fallback to a dummy if testing
        $apiKey = config('services.openai.key') ?? env('OPENAI_API_KEY');
        
        if (empty($apiKey)) {
            return response()->json([
                'error' => 'OpenAI API key not configured. Please set OPENAI_API_KEY in .env'
            ], 400);
        }

        $prompt = "You are an expert software project cost estimator specializing in the Egyptian freelancing market.
Analyze the following project description and generate a professional project proposal with a detailed cost breakdown in Egyptian Pounds (EGP).

Project Description:
{$projectDetails}

Return ONLY valid JSON in this exact format:
{
  \"project_name\": \"Suggested Title\",
  \"summary\": \"Executive summary\",
  \"value_proposition\": \"Why this is valuable\",
  \"total_duration_days\": 15,
  \"includes_hosting\": false,
  \"hosting_duration\": \"1 Year\",
  \"includes_support\": true,
  \"support_duration\": \"1 Month\",
  \"cost_breakdown\": [
    {
      \"item\": \"Task name\",
      \"description\": \"Description\",
      \"frequency\": \"One-time\",
      \"subtotal_egp\": 1000
    }
  ],
  \"total_cost_egp\": 4000
}";

        try {
            $response = Http::timeout(60)->withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->post("https://api.openai.com/v1/chat/completions", [
                "model" => "gpt-4o-mini", // Cost-effective model
                "messages" => [
                    [
                        "role" => "system",
                        "content" => "You are an expert software estimator. Always return raw JSON, no markdown formatting like ```json."
                    ],
                    [
                        "role" => "user",
                        "content" => $prompt
                    ]
                ],
                "temperature" => 0.7,
            ]);

            if ($response->failed()) {
                throw new \Exception("OpenAI API Error: " . $response->body());
            }

            $aiResponse = $response->json();
            $content = $aiResponse['choices'][0]['message']['content'] ?? '';
            
            // Clean markdown if present
            $content = preg_replace('/```json\s*/', '', $content);
            $content = preg_replace('/```\s*/', '', $content);
            
            $parsed = json_decode(trim($content), true);
            
            if (!$parsed) {
                throw new \Exception("Failed to parse AI response as JSON.");
            }
            
            // Ensure we have a total cost
            $totalCost = $parsed['total_cost_egp'] ?? array_sum(array_column($parsed['cost_breakdown'] ?? [], 'subtotal_egp'));

            // Build simple ASCII
            $ascii = "PROPOSAL: " . ($parsed['project_name'] ?? 'Project') . "\n";
            $ascii .= "TOTAL ESTIMATE: " . number_format($totalCost, 2) . " EGP\n";
            $ascii .= "DURATION: " . ($parsed['total_duration_days'] ?? 0) . " Days\n\n";
            $ascii .= "BREAKDOWN:\n";
            foreach ($parsed['cost_breakdown'] ?? [] as $item) {
                $ascii .= "- " . ($item['item'] ?? '') . ": " . number_format($item['subtotal_egp'] ?? 0, 2) . " EGP\n";
            }

            return response()->json([
                'project_details' => $projectDetails,
                'parsed_data' => $parsed,
                'total_cost_egp' => $totalCost,
                'ascii_table' => $ascii
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function saveProposal(Request $request)
    {
        $request->validate([
            'project_details' => 'required',
            'parsed_data' => 'required|array',
            'total_cost_egp' => 'required|numeric',
            'ascii_table' => 'required'
        ]);

        $parsed = $request->input('parsed_data');

        $proposal = PlatformProposal::create([
            'user_id' => Auth::id(),
            'project_name' => $parsed['project_name'] ?? 'Untitled Proposal',
            'project_details' => $request->input('project_details'),
            'total_cost_egp' => $request->input('total_cost_egp'),
            'total_duration_days' => $parsed['total_duration_days'] ?? 0,
            'cost_breakdown' => $parsed['cost_breakdown'] ?? [],
            'proposal_data' => $parsed,
            'ascii_table' => $request->input('ascii_table'),
            'status' => 'draft'
        ]);

        return redirect()->back()->with('success', 'Proposal saved successfully!');
    }

    public function convertToContract(Request $request, PlatformProposal $proposal)
    {
        $contract = PlatformContract::create([
            'user_id' => $proposal->user_id,
            'project_name' => $proposal->project_name,
            'project_description' => $proposal->project_details,
            'reference' => 'CTR-' . strtoupper(uniqid()),
            'duration' => $proposal->total_duration_days . ' Days',
            'total_amount' => $proposal->total_cost_egp,
            'currency' => 'EGP',
            'items' => $proposal->cost_breakdown,
            'content' => $proposal->proposal_data,
            'status' => 'draft',
        ]);

        $proposal->update(['status' => 'converted_to_contract']);

        return redirect()->route('admin.contracts.index')->with('success', 'Contract generated from proposal!');
    }
}
