<?php

namespace App\Services\AI\Tools;

use App\Models\Project;

interface AiToolInterface
{
    /**
     * Unique identifier name for the tool.
     */
    public function name(): string;

    /**
     * Human-readable description of what the tool does.
     */
    public function description(): string;

    /**
     * JSON Schema parameters definition for LLM tool declaration.
     */
    public function parameters(): array;

    /**
     * Execute the tool logic on the project.
     *
     * @param Project $project
     * @param array $arguments Arguments passed to the tool
     * @return array Result array containing ['success' => bool, 'action' => string, 'detail' => string]
     */
    public function execute(Project $project, array $arguments): array;
}
