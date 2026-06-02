<?php

namespace Modules\WrittenCoursesEngine\app\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CourseLoaderService
{
    protected MarkdownParserService $parser;

    public function __construct(MarkdownParserService $parser)
    {
        $this->parser = $parser;
    }

    /**
     * Get the full tree of all courses.
     * Caches the result to avoid constant disk I/O.
     */
    public function getAllCoursesTree(): array
    {
        return Cache::rememberForever('written_courses_tree', function () {
            $courses = [];
            $disk = Storage::disk('local');
            $basePath = 'written-courses';

            if (!$disk->exists($basePath)) {
                return [];
            }

            $directories = $disk->directories($basePath);

            foreach ($directories as $dir) {
                $slug = basename($dir);
                $courseData = $this->loadCourse($slug);
                if ($courseData) {
                    $courses[] = $courseData;
                }
            }

            return $courses;
        });
    }

    /**
     * Load a specific course by slug.
     */
    public function loadCourse(string $slug): ?array
    {
        $disk = Storage::disk('local');
        $basePath = "written-courses/{$slug}";

        if (!$disk->exists($basePath . '/course.md')) {
            return null;
        }

        $courseContent = $disk->get($basePath . '/course.md');
        $parsed = $this->parser->parse($courseContent);
        
        $courseData = $parsed['frontmatter'];
        $courseData['slug'] = $slug;
        $courseData['modules'] = $this->loadModules($basePath . '/modules');

        return $courseData;
    }

    /**
     * Load modules for a course.
     */
    private function loadModules(string $modulesPath): array
    {
        $disk = Storage::disk('local');
        if (!$disk->exists($modulesPath)) {
            return [];
        }

        $modules = [];
        $directories = $disk->directories($modulesPath);

        foreach ($directories as $dir) {
            $slug = basename($dir);
            if ($disk->exists($dir . '/module.md')) {
                $content = $disk->get($dir . '/module.md');
                $parsed = $this->parser->parse($content);
                
                $moduleData = $parsed['frontmatter'];
                $moduleData['slug'] = $slug;
                $moduleData['lessons'] = $this->loadLessons($dir . '/lessons');
                
                $modules[] = $moduleData;
            }
        }

        usort($modules, fn($a, $b) => ($a['order'] ?? 99) <=> ($b['order'] ?? 99));

        return $modules;
    }

    /**
     * Load lessons for a module.
     */
    private function loadLessons(string $lessonsPath): array
    {
        $disk = Storage::disk('local');
        if (!$disk->exists($lessonsPath)) {
            return [];
        }

        $lessons = [];
        $directories = $disk->directories($lessonsPath);

        foreach ($directories as $dir) {
            $slug = basename($dir);
            if ($disk->exists($dir . '/lesson.md')) {
                $content = $disk->get($dir . '/lesson.md');
                $parsed = $this->parser->parse($content);
                
                $lessonData = $parsed['frontmatter'];
                $lessonData['slug'] = $slug;
                
                $lessons[] = $lessonData;
            }
        }

        usort($lessons, fn($a, $b) => ($a['order'] ?? 99) <=> ($b['order'] ?? 99));

        return $lessons;
    }

    /**
     * Load the full markdown content for a specific lesson dynamically.
     */
    public function getLessonContent(string $courseSlug, string $moduleSlug, string $lessonSlug): ?array
    {
        $disk = Storage::disk('local');
        // Prevent path traversal
        $courseSlug = Str::slug($courseSlug);
        $moduleSlug = Str::slug($moduleSlug);
        $lessonSlug = Str::slug($lessonSlug);
        
        $path = "written-courses/{$courseSlug}/modules/{$moduleSlug}/lessons/{$lessonSlug}/lesson.md";

        if (!$disk->exists($path)) {
            return null;
        }

        return $this->parser->parse($disk->get($path));
    }
    
    /**
     * Load the full markdown content for a specific course overview dynamically.
     */
    public function getCourseContent(string $courseSlug): ?array
    {
        $disk = Storage::disk('local');
        $courseSlug = Str::slug($courseSlug);
        
        $path = "written-courses/{$courseSlug}/course.md";

        if (!$disk->exists($path)) {
            return null;
        }

        return $this->parser->parse($disk->get($path));
    }
}
