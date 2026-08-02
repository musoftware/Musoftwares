<?php

namespace App\Services\AI\Tools;

use App\Models\Project;

class UpdateDocumentationTool implements AiToolInterface
{
    public function name(): string { return 'update_documentation'; }
    public function description(): string { return 'Updates AI project technical documentation spec.'; }
    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'doc_summary' => ['type' => 'string'],
            ],
            'required' => ['doc_summary'],
        ];
    }

    public function execute(Project $project, array $arguments): array
    {
        $doc = $arguments['doc_summary'] ?? 'Updated project specification.';

        return [
            'success' => true,
            'action'  => 'Updated Technical Specs',
            'detail'  => mb_strimwidth($doc, 0, 60, '…'),
        ];
    }
}
