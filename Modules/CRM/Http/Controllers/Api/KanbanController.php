<?php

namespace Modules\CRM\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KanbanController extends Controller
{
    /**
     * High-performance, paginated fetch for the React Kanban board.
     * Prevents OOM crashes by strictly limiting row counts per stage.
     */
    public function index(Request $request)
    {
        $tenantId = session('tenant_id') ?? $request->user()->tenant_id;
        $userId = $request->user()->id;

        $stages = ['NEW', 'FOLLOW_UP', 'INTERESTED', 'NEGOTIATION', 'MEETING_SCHEDULED'];
        $results = [];

        foreach ($stages as $stage) {
            $leads = DB::table('leads')
                ->where('tenant_id', $tenantId)
                ->where('assigned_to_id', $userId)
                ->where('pipeline_stage', $stage)
                ->orderBy('updated_at', 'desc')
                ->cursorPaginate(15, ['*'], 'cursor_' . strtolower($stage));

            $results[$stage] = [
                'data' => $leads->items(),
                'next_cursor' => $leads->nextCursor() ? $leads->nextCursor()->encode() : null,
            ];
        }

        return response()->json($results);
    }
}
