<?php

namespace App\Services\AI\Tools;

use App\Models\Project;
use Illuminate\Support\Str;

class AskCustomerQuestionsTool implements AiToolInterface
{
    public function name(): string
    {
        return 'ask_customer_questions';
    }

    public function description(): string
    {
        return 'Generates clarification questions for missing information and displays them to the client.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'questions' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                    'description' => 'Questions to ask the client',
                ],
            ],
            'required' => ['questions'],
        ];
    }

    public function execute(Project $project, array $arguments): array
    {
        $newQuestions = $arguments['questions'] ?? [];
        if (empty($newQuestions)) {
            return ['success' => false, 'action' => 'Ask Customer Questions', 'detail' => 'No questions provided'];
        }

        $existing = $project->ai_questions ?? [];
        foreach ($newQuestions as $q) {
            $existing[] = [
                'id'       => (string) Str::uuid(),
                'question' => $q,
                'answered' => false,
            ];
        }

        $project->update(['ai_questions' => $existing]);

        return [
            'success' => true,
            'action'  => 'Generated ' . count($newQuestions) . ' Clarification Question(s)',
            'detail'  => implode(' | ', $newQuestions),
        ];
    }
}
