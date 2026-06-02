<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

use Modules\WrittenCoursesEngine\app\Http\Controllers\CourseApiController;
use Modules\WrittenCoursesEngine\app\Http\Controllers\AdminCourseApiController;

Route::prefix('written-courses')->group(function() {
    Route::get('/', [CourseApiController::class, 'index']);
    Route::get('/{courseSlug}', [CourseApiController::class, 'show']);
    Route::get('/{courseSlug}/modules/{moduleSlug}/lessons/{lessonSlug}', [CourseApiController::class, 'getLesson']);
});

Route::prefix('admin/written-courses')->middleware(['auth:sanctum'])->group(function() {
    Route::post('/generate', [AdminCourseApiController::class, 'generateCourse']);
});
