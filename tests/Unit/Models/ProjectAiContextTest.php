<?php

namespace Tests\Unit\Models;

use App\Models\Project;
use Tests\TestCase;

class ProjectAiContextTest extends TestCase
{
    public function test_update_ai_context_handles_nested_arrays_without_array_to_string_conversion_error(): void
    {
        $project = new Project();
        $project->ai_context = [
            'pending_features' => ['Feature A', ['title' => 'Feature B', 'desc' => 'Nested']],
            'tech_stack' => ['Laravel', 'React'],
        ];

        // Call updateAiContext with nested arrays
        $updates = [
            'pending_features' => [
                ['title' => 'Feature C', 'desc' => 'Another Nested'],
                'Feature A',
            ],
            'tech_stack' => ['Vue', 'Laravel'],
        ];

        // Ensure updateAiContext doesn't throw ErrorException (Array to string conversion)
        $project->updateAiContext($updates);

        $context = $project->ai_context;
        $this->assertIsArray($context);
        $this->assertCount(3, $context['pending_features']);
        $this->assertCount(3, $context['tech_stack']);
    }
}
