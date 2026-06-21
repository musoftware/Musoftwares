<?php

namespace App\Http\Controllers;

use App\Models\BackgroundTask;
use Illuminate\Http\Request;

class BackgroundTaskController extends Controller
{
    /**
     * Get the list of background tasks for the authenticated user.
     */
    public function index(Request $request)
    {
        $tasks = BackgroundTask::where('user_id', $request->user()->id)
            ->latest()
            ->paginate(20);

        return response()->json($tasks);
    }

    /**
     * Get a specific background task.
     */
    public function show(Request $request, BackgroundTask $backgroundTask)
    {
        if ($backgroundTask->user_id !== $request->user()->id) {
            abort(403);
        }

        return response()->json($backgroundTask);
    }
}
