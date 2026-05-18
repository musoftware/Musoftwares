<?php

namespace Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\ERP\Models\TenantClient;
use Modules\ERP\Models\Invoice;
use Modules\ERP\Models\Project;
use Modules\ERP\Models\SupportTicket;
use Modules\ERP\Models\Activity;
use Modules\ERP\Services\ActivityLogger;
use Inertia\Inertia;

class ClientController extends Controller
{
    /**
     * Show the complete operational workflow for a specific client.
     */
    public function show(TenantClient $client)
    {
        // Ensure the client belongs to the active tenant
        if ($client->tenant_id !== session('tenant_id')) {
            abort(403, 'Unauthorized access to client.');
        }

        // Load relationships
        $client->load(['projects', 'supportTickets']);

        // Fetch related operational data
        $invoices = Invoice::where('client_id', $client->id)->latest()->get();
        $activities = Activity::where('subject_type', TenantClient::class)
            ->where('subject_id', $client->id)
            ->with('causer')
            ->latest()
            ->get()
            ->map(function ($activity) {
                return [
                    'title' => $activity->action,
                    'time' => $activity->created_at?->diffForHumans(),
                    'description' => $activity->description,
                    'user' => $activity->causer?->name ?? 'System',
                ];
            });

        return Inertia::render('ERP/Clients/Show', [
            'client' => $client,
            'projects' => $client->projects,
            'tickets' => $client->supportTickets,
            'invoices' => $invoices,
            'activities' => $activities,
        ]);
    }

    /**
     * Update client status (e.g., Lead -> Active -> Retained)
     */
    public function updateStatus(Request $request, TenantClient $client)
    {
        if ($client->tenant_id !== session('tenant_id')) {
            abort(403);
        }

        $request->validate([
            'status' => 'required|in:lead,active,paying,retained,archived'
        ]);

        $oldStatus = $client->status;
        $client->update(['status' => $request->status]);

        ActivityLogger::log(
            'client_status_changed',
            "Client status changed from {$oldStatus} to {$client->status}.",
            $client,
            $client->id
        );

        return back()->with('success', 'Client status updated.');
    }
}
