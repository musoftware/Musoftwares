<?php

namespace Modules\CRM\Services;

use App\Models\Sequence;
use App\Models\SequenceStep;
use Illuminate\Support\Facades\Http;

class SequenceService
{
    public function createSequence(array $data): Sequence
    {
        return Sequence::create($data);
    }

    public function deleteSequence(Sequence $sequence): void
    {
        $sequence->delete();
    }

    public function addStep(Sequence $sequence, array $data): SequenceStep
    {
        return $sequence->steps()->create([
            'order'            => $data['order'],
            'delay'            => $data['delay'],
            'unit'             => $data['unit'],
            'send_email'       => $data['send_email'] ?? false,
            'send_whatsapp'    => $data['send_whatsapp'] ?? false,
            'email_subject'    => ['en' => $data['email_subject_en'] ?? ''],
            'email_content'    => ['en' => $data['email_content_en'] ?? ''],
            'whatsapp_content' => ['en' => $data['whatsapp_content_en'] ?? ''],
        ]);
    }

    public function updateStep(SequenceStep $step, array $data): void
    {
        $step->update([
            'order'            => $data['order'],
            'delay'            => $data['delay'],
            'unit'             => $data['unit'],
            'send_email'       => $data['send_email'] ?? false,
            'send_whatsapp'    => $data['send_whatsapp'] ?? false,
            'email_subject'    => ['en' => $data['email_subject_en'] ?? ''],
            'email_content'    => ['en' => $data['email_content_en'] ?? ''],
            'whatsapp_content' => ['en' => $data['whatsapp_content_en'] ?? ''],
        ]);
    }

    public function deleteStep(SequenceStep $step): void
    {
        $step->delete();
    }

    public function generateStepsWithAI(int $numSteps, string $context, string $tone): array
    {
        $apiKey = config('services.openai.key') ?? env('OPENAI_API_KEY');
        if (empty($apiKey)) {
            throw new \Exception('OpenAI API key missing');
        }

        $prompt = "You are a marketing automation expert. Generate {$numSteps} sequence steps for a drip campaign.
Context: {$context}
Tone: {$tone}

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

        $response = Http::timeout(60)->withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type'  => 'application/json',
        ])->post("https://api.openai.com/v1/chat/completions", [
            "model"    => "gpt-4o-mini",
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

        return $steps;
    }

    public function applyGeneratedSteps(Sequence $sequence, array $steps): void
    {
        $currentMaxOrder = $sequence->steps()->max('order') ?? 0;

        foreach ($steps as $step) {
            $sequence->steps()->create([
                'order'            => $currentMaxOrder + ($step['order'] ?? 1),
                'delay'            => $step['delay'] ?? 1,
                'unit'             => $step['unit'] ?? 'day',
                'send_email'       => $step['send_email'] ?? false,
                'send_whatsapp'    => $step['send_whatsapp'] ?? false,
                'email_subject'    => ['en' => $step['email_subject_en'] ?? ''],
                'email_content'    => ['en' => $step['email_content_en'] ?? ''],
                'whatsapp_content' => ['en' => $step['whatsapp_content_en'] ?? ''],
            ]);
            $currentMaxOrder++;
        }
    }
}
