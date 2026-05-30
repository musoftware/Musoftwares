<?php

namespace App\Services;

use App\Models\Campaign;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class CampaignService
{
    /**
     * Create a new campaign.
     */
    public function createCampaign(array $data): Campaign
    {
        return DB::transaction(fn() => Campaign::create($data));
    }

    /**
     * Update an existing campaign.
     */
    public function updateCampaign(Campaign $campaign, array $data): void
    {
        DB::transaction(fn() => $campaign->update($data));
    }

    /**
     * Delete a campaign.
     */
    public function deleteCampaign(Campaign $campaign): void
    {
        DB::transaction(fn() => $campaign->delete());
    }

    /**
     * Schedule a campaign.
     */
    public function scheduleCampaign(Campaign $campaign): void
    {
        DB::transaction(function () use ($campaign) {
            // Business logic for scheduling
            $campaign->update(['status' => 'scheduled']);
        });
    }

    /**
     * Pause a running campaign.
     */
    public function pauseCampaign(Campaign $campaign): void
    {
        DB::transaction(fn() => $campaign->update(['status' => 'paused']));
    }

    /**
     * Resume a paused campaign.
     */
    public function resumeCampaign(Campaign $campaign): void
    {
        DB::transaction(fn() => $campaign->update(['status' => 'active']));
    }

    public function generateAIContent(string $context, string $tone, string $type): array
    {
        $apiKey = config('services.openai.key') ?? env('OPENAI_API_KEY');
        if (empty($apiKey)) {
            throw new \Exception('OpenAI API key missing');
        }

        $typeInstruction = match ($type) {
            'email' => 'an engaging Email Subject and Email Body',
            'whatsapp' => 'a short, punchy WhatsApp broadcast message',
            'mixed' => 'an engaging Email Subject, Email Body, AND a short WhatsApp broadcast message'
        };

        $prompt = "You are an expert copywriter. Write {$typeInstruction} based on this context.
Context: {$context}
Tone: {$tone}

Return ONLY valid JSON with this structure:
{
  \"email_subject_en\": \"...\",
  \"email_content_en\": \"...\",
  \"whatsapp_content_en\": \"...\"
}";

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

        return $generated;
    }
}
