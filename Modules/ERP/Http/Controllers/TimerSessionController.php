<?php

namespace Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\ERP\Models\TimerSession;
use Illuminate\Http\Request;

/**
 * API endpoint for polling a TimerSession's current state.
 * Used by the frontend timer UI to get live duration updates.
 */
class TimerSessionController extends Controller
{
    public function show(Request $request, int $id)
    {
        $timer = TimerSession::withoutGlobalScopes()->find($id);

        if (!$timer) {
            return response()->json(['duration_seconds' => 0, 'stopped_at' => now()]);
        }

        return response()->json([
            'id'               => $timer->id,
            'duration_seconds' => $timer->duration_seconds,
            'stopped_at'       => $timer->stopped_at,
            'status'           => $timer->status,
        ]);
    }
}
