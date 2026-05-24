<?php

namespace Modules\WrittenCoursesEngine\App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\WrittenCoursesEngine\App\Services\AiGenerationService;
use Illuminate\Support\Facades\Artisan;

class AdminCourseApiController extends Controller
{
    protected AiGenerationService $generator;

    public function __construct(AiGenerationService $generator)
    {
        $this->generator = $generator;
    }

    /**
     * Trigger the AI to generate a course from a blueprint text.
     */
    public function generateCourse(Request $request)
    {
        $request->validate([
            'blueprint' => 'required|string|min:10',
        ]);

        try {
            $slug = $this->generator->generateCourseFromBlueprint($request->input('blueprint'));
            
            // Clear the course tree cache
            \Cache::forget('written_courses_tree');

            return response()->json([
                'message' => 'Course generated successfully',
                'course_slug' => $slug
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Generation failed', 'error' => $e->getMessage()], 500);
        }
    }
}
