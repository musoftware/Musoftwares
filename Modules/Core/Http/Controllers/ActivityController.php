<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Core\Services\ActivityService;
use Inertia\Inertia;

class ActivityController extends Controller
{
    /**
     * Global activity feed — paginated, filterable.
     * GET /activity
     */
    public function index(Request $request)
    {
        $workspace = $request->input('workspace');
        $event     = $request->input('event');
        $userId    = $request->input('user_id');

        $activities = ActivityService::feed(
            workspace: $workspace ?: null,
            event:     $event ?: null,
            userId:    $userId ? (int) $userId : null,
            perPage:   30,
        );

        return Inertia::render('Activity/Index', [
            'activities' => $activities,
            'filters'    => $request->only(['workspace', 'event', 'user_id']),
        ]);
    }

    /**
     * JSON API endpoint — used by inline feed widgets on dashboards.
     * GET /api/activity
     */
    public function feed(Request $request)
    {
        $activities = ActivityService::feed(
            workspace: $request->input('workspace'),
            event:     $request->input('event'),
            perPage:   20,
        );

        return response()->json($activities);
    }
}
