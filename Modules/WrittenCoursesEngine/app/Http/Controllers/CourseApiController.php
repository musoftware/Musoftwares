<?php

namespace Modules\WrittenCoursesEngine\App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\WrittenCoursesEngine\App\Services\CourseLoaderService;

class CourseApiController extends Controller
{
    protected CourseLoaderService $loader;

    public function __construct(CourseLoaderService $loader)
    {
        $this->loader = $loader;
    }

    /**
     * Get a list of all courses (tree).
     */
    public function index()
    {
        $courses = $this->loader->getAllCoursesTree();
        return response()->json(['data' => $courses]);
    }

    /**
     * Get course details and module tree.
     */
    public function show($courseSlug)
    {
        $course = $this->loader->loadCourse($courseSlug);
        
        if (!$course) {
            return response()->json(['message' => 'Course not found'], 404);
        }
        
        return response()->json(['data' => $course]);
    }

    /**
     * Get specific lesson markdown content.
     */
    public function getLesson($courseSlug, $moduleSlug, $lessonSlug)
    {
        $lesson = $this->loader->getLessonContent($courseSlug, $moduleSlug, $lessonSlug);
        
        if (!$lesson) {
            return response()->json(['message' => 'Lesson not found'], 404);
        }
        
        return response()->json(['data' => $lesson]);
    }
}
