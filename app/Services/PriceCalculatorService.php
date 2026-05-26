<?php

namespace App\Services;

use App\Models\Billing\PlatformContract;
use App\Models\ProjectProposal;
use Illuminate\Support\Facades\Http;

class PriceCalculatorService
{
    public function calculateAI(string $projectDetails): array
    {
        $apiKey = config('services.openai.key') ?? env('OPENAI_API_KEY');
        
        if (empty($apiKey)) {
            throw new \Exception('OpenAI API key not configured. Please set OPENAI_API_KEY in .env');
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

        return [
            'project_details' => $projectDetails,
            'parsed_data' => $parsed,
            'total_cost_egp' => $totalCost,
            'ascii_table' => $ascii
        ];
    }

    public function saveProposal(int $userId, array $data): ProjectProposal
    {
        $parsed = $data['parsed_data'];

        return ProjectProposal::create([
            'user_id' => $userId,
            'project_name' => $parsed['project_name'] ?? 'Untitled Proposal',
            'project_details' => $data['project_details'],
            'total_cost_egp' => $data['total_cost_egp'],
            'total_duration_days' => $parsed['total_duration_days'] ?? 0,
            'cost_breakdown' => $parsed['cost_breakdown'] ?? [],
            'proposal_data' => $parsed,
            'ascii_table' => $data['ascii_table'],
        ]);
    }

    public function convertToContract(ProjectProposal $proposal): PlatformContract
    {
        $contract = PlatformContract::create([
            'user_id' => $proposal->user_id,
            'project_name' => $proposal->project_name,
            'project_description' => $proposal->project_details,
            'reference' => 'CTR-' . strtoupper(uniqid()),
            'duration' => $proposal->total_duration_days . ' Days',
            'total_amount' => $proposal->total_cost_egp,
            'currency_id' => 2,
            'items' => $proposal->cost_breakdown,
            'content' => $proposal->proposal_data,
            'status' => 'draft',
        ]);

        return $contract;
    }
}
