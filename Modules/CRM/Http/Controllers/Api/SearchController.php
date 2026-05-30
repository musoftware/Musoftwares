<?php

namespace Modules\CRM\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\CRM\Models\Lead;

class SearchController extends Controller
{
    /**
     * Perform a universal search across the CRM.
     */
    public function index(Request $request)
    {
        $query = $request->input('q');
        $workspaceId = session('crm_workspace_id');

        if (!$query || strlen($query) < 2) {
            return response()->json([]);
        }

        $results = [];

        // 1. Search Leads
        $leads = Lead::where('workspace_id', $workspaceId)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%")
                  ->orWhere('phone', 'like', "%{$query}%")
                  ->orWhere('company', 'like', "%{$query}%");
            })
            ->limit(5)
            ->get();

        foreach ($leads as $lead) {
            $results[] = [
                'id' => "lead_{$lead->id}",
                'type' => 'Lead',
                'title' => $lead->name,
                'subtitle' => $lead->email ?? $lead->phone ?? $lead->company,
                'url' => route('crm.leads.index', ['search' => $lead->name]),
                'action_id' => $lead->id, // Frontend can use this to open SlideOver
            ];
        }

        // Add more search modules here based on feature flags (Campaigns, Tasks, etc)

        return response()->json($results);
    }
}
