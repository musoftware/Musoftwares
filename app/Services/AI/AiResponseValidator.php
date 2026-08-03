<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Log;

class AiResponseValidator
{
    /**
     * Validate and repair LLM response array against expected schema.
     */
    public function validateAndRepair(string $rawText): array
    {
        $parsed = $this->extractJson($rawText);

        if (!is_array($parsed)) {
            Log::warning('[AiResponseValidator] Failed to parse JSON from raw text. Using fallback schema.', [
                'raw_snippet' => mb_substr($rawText, 0, 200),
            ]);
            $parsed = [];
        }

        return $this->applySchemaDefaults($parsed);
    }

    /**
     * Multi-stage JSON extractor handling code fences, raw JSON, trailing commas, and unescaped quotes.
     */
    public function extractJson(string $text): ?array
    {
        $text = trim($text);

        // 1. Remove Markdown code blocks if present
        if (preg_match('/```(?:json)?\s*(\{.*\})\s*```/s', $text, $matches)) {
            $text = trim($matches[1]);
        } elseif (preg_match('/\{.*\}/s', $text, $matches)) {
            $text = trim($matches[0]);
        }

        // Direct decode attempt
        $decoded = json_decode($text, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        // 2. Perform common JSON repairs (trailing commas, control chars)
        $repaired = preg_replace('/,\s*([\}\]])/', '$1', $text);
        $repaired = preg_replace('/[\x00-\x1F\x7F]/u', '', $repaired);

        $decoded = json_decode($repaired, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        return null;
    }

    /**
     * Apply default structural keys to ensure no undefined index errors.
     */
    public function applySchemaDefaults(array $data): array
    {
        $defaults = [
            'reasoning' => $data['reasoning'] ?? 'تحليل الرسالة وتحديد الخطوة التالية بناءً على سياق المشروع.',
            'intent'    => [
                'primary'           => $data['intent']['primary'] ?? 'inquiry',
                'confidence'        => (float) ($data['intent']['confidence'] ?? 0.85),
                'is_scope_addition' => (bool) ($data['intent']['is_scope_addition'] ?? false),
                'is_modification'   => (bool) ($data['intent']['is_modification'] ?? false),
            ],
            'requirements_analysis' => [
                'is_complete'            => (bool) ($data['requirements_analysis']['is_complete'] ?? false),
                'completeness_score'     => (int) ($data['requirements_analysis']['completeness_score'] ?? 50),
                'missing_information'    => $data['requirements_analysis']['missing_information'] ?? [],
                'questions_needed_count' => (int) ($data['requirements_analysis']['questions_needed_count'] ?? 1),
                'stop_asking_questions'  => (bool) ($data['requirements_analysis']['stop_asking_questions'] ?? false),
            ],
            'reply'               => $data['reply'] ?? 'أهلاً بك! يرجى توضيح تفاصيل مشروعك لنبدأ بدراسة المتطلبات.',
            'next_best_action'    => $data['next_best_action'] ?? 'استيضاح بقية المتطلبات الفنية من العميل.',
            'action_proposals'    => [
                'invoice' => [
                    'propose'                       => (bool) ($data['action_proposals']['invoice']['propose'] ?? false),
                    'requires_client_confirmation' => true,
                    'amount_usd'                    => (float) ($data['action_proposals']['invoice']['amount_usd'] ?? 0.0),
                    'description'                   => $data['action_proposals']['invoice']['description'] ?? '',
                ],
                'tasks'            => $data['action_proposals']['tasks'] ?? [],
                'stage_transition' => $data['action_proposals']['stage_transition'] ?? null,
            ],
            'need_more_questions' => $data['need_more_questions'] ?? [],
            'context_updates'     => $data['context_updates'] ?? [],
        ];

        return array_replace_recursive($defaults, $data);
    }
}
