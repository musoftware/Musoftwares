<?php

namespace App\Services\AI\Tools;

use App\Models\Project;
use Illuminate\Support\Str;

class DetectConflictsTool implements AiToolInterface
{
    public function name(): string { return 'detect_conflicts'; }
    public function description(): string { return 'Scans client requests for conflicting or contradictory instructions.'; }
    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'conflict_description' => ['type' => 'string', 'description' => 'Description of detected contradictory instructions'],
            ],
            'required' => ['conflict_description'],
        ];
    }

    public function execute(Project $project, array $arguments): array
    {
        $desc = $arguments['conflict_description'] ?? 'Contradictory requirement detected.';

        $questions = $project->ai_questions ?? [];
        $questions[] = [
            'id'       => (string) Str::uuid(),
            'question' => '⚠️ Conflict Detected: ' . $desc . ' Please clarify which option you prefer.',
            'answered' => false,
        ];

        $project->update(['ai_questions' => $questions]);

        return [
            'success' => true,
            'action'  => 'Detected Requirement Conflict',
            'detail'  => $desc,
        ];
    }
}
