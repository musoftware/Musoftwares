<?php

namespace Modules\CRM\Domains\Automation\Actions;

use Illuminate\Support\Facades\Http;
use Exception;

class GenerateCampaignAIContentAction
{
    /**
     * @return array{email_subject_en: string, email_content_en: string, whatsapp_content_en: string}
     */
    public function execute(string $context, string $tone, string $type): array
    {
        $apiKey = config('services.openai.key') ?? env('OPENAI_API_KEY');
        if (empty($apiKey)) {
            throw new Exception('OpenAI API key missing');
        }

        $typeInstruction = match ($type) {
            'email' => 'an engaging Email Subject and Email Body',
            'whatsapp' => 'a short, punchy WhatsApp broadcast message',
            'mixed' => 'an engaging Email Subject, Email Body, AND a short WhatsApp broadcast message',
            default => 'an engaging short broadcast message'
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
            throw new Exception("Invalid JSON from AI.");
        }

        return $generated;
    }
}
