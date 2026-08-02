<?php

namespace App\Services\AI\Tools;

use App\Models\Project;

class ExtractImportantFilesTool implements AiToolInterface
{
    public function name(): string { return 'extract_important_files'; }
    public function description(): string { return 'Analyzes uploaded files in chat, categorizing their purpose (design, spec, media, asset).'; }
    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'filename' => ['type' => 'string'],
                'category' => ['type' => 'string', 'enum' => ['design', 'specification', 'asset', 'contract', 'media']],
            ],
            'required' => ['filename', 'category'],
        ];
    }

    public function execute(Project $project, array $arguments): array
    {
        $file = $arguments['filename'] ?? 'file';
        $cat  = $arguments['category'] ?? 'asset';

        return [
            'success' => true,
            'action'  => 'Categorized Uploaded File (' . ucfirst($cat) . ')',
            'detail'  => $file,
        ];
    }
}
