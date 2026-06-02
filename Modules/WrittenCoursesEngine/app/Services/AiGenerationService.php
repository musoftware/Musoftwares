<?php

namespace Modules\WrittenCoursesEngine\app\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AiGenerationService
{
    /**
     * Generate a full course structure from a blueprint using an LLM.
     * In a real implementation, this would call an API like OpenAI/Gemini.
     */
    public function generateCourseFromBlueprint(string $blueprintText): string
    {
        // 1. Call LLM to parse blueprint and return a structured JSON course tree
        $courseTree = $this->simulateLlmBlueprintParsing($blueprintText);
        
        $courseSlug = Str::slug($courseTree['title']);
        $basePath = "written-courses/{$courseSlug}";
        
        $disk = Storage::disk('local');
        
        // 2. Generate Course Metadata and Overview
        $courseFrontmatter = $this->buildFrontmatter([
            'title' => $courseTree['title'],
            'slug' => $courseSlug,
            'description' => $courseTree['description'],
            'difficulty' => 'intermediate',
            'tags' => ['ai-generated'],
            'status' => 'draft',
            'version' => '1.0.0',
        ]);
        
        $disk->put("{$basePath}/course.md", $courseFrontmatter . "\n# " . $courseTree['title'] . "\n\n" . $courseTree['description']);
        $disk->put("{$basePath}/metadata.json", json_encode(['blueprint' => $blueprintText], JSON_PRETTY_PRINT));
        
        // 3. Generate Modules and Lessons
        foreach ($courseTree['modules'] as $index => $module) {
            $moduleSlug = Str::slug($module['title']);
            $modulePath = "{$basePath}/modules/{$moduleSlug}";
            
            $moduleFrontmatter = $this->buildFrontmatter([
                'title' => $module['title'],
                'slug' => $moduleSlug,
                'description' => $module['description'],
                'order' => $index + 1,
                'status' => 'draft',
            ]);
            
            $disk->put("{$modulePath}/module.md", $moduleFrontmatter . "\n# " . $module['title']);
            
            foreach ($module['lessons'] as $lIndex => $lesson) {
                $lessonSlug = Str::slug($lesson['title']);
                $lessonPath = "{$modulePath}/lessons/{$lessonSlug}";
                
                // 4. In a real system, we would make another LLM call here to write the actual lesson content
                $lessonContent = $this->simulateLlmContentGeneration($lesson['title']);
                
                $lessonFrontmatter = $this->buildFrontmatter([
                    'title' => $lesson['title'],
                    'slug' => $lessonSlug,
                    'description' => 'A lesson about ' . $lesson['title'],
                    'order' => $lIndex + 1,
                    'status' => 'draft',
                ]);
                
                $disk->put("{$lessonPath}/lesson.md", $lessonFrontmatter . "\n# " . $lesson['title'] . "\n\n" . $lessonContent);
                // Scaffold additional folders
                $disk->makeDirectory("{$lessonPath}/assets");
                $disk->makeDirectory("{$lessonPath}/examples");
                $disk->makeDirectory("{$lessonPath}/prompts");
            }
        }
        
        return $courseSlug;
    }

    private function buildFrontmatter(array $data): string
    {
        $yaml = "---\n";
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $yaml .= "{$key}:\n";
                foreach ($value as $item) {
                    $yaml .= "  - {$item}\n";
                }
            } else {
                $value = str_replace('"', '\"', $value);
                $yaml .= "{$key}: \"{$value}\"\n";
            }
        }
        $yaml .= "---\n";
        return $yaml;
    }

    private function simulateLlmBlueprintParsing(string $text): array
    {
        return [
            'title' => 'AI Generated Course: ' . substr($text, 0, 20),
            'description' => 'Automatically generated course structure.',
            'modules' => [
                [
                    'title' => 'Introduction',
                    'description' => 'Basics of the topic.',
                    'lessons' => [
                        ['title' => 'Getting Started'],
                        ['title' => 'Core Concepts'],
                    ]
                ]
            ]
        ];
    }
    
    private function simulateLlmContentGeneration(string $title): string
    {
        return "This is the generated content for {$title}.\n\n## Section 1\nDiscussing the main ideas.";
    }
}
