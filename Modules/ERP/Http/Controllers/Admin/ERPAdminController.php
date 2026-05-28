<?php

namespace Modules\ERP\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\ERP\Models\Tenant;
use Modules\ERP\Models\Invoice;
use Modules\ERP\Models\TenantClient;
use Modules\ERP\Models\TeamMember;
use Modules\ERP\Models\ERPTask;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Support\Facades\DB;

class ERPAdminController extends Controller
{
    /**
     * List all ERP tenants with stats.
     */
    public function index(Request $request): InertiaResponse
    {
        $query = Tenant::with('user');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $tenants = $query->paginate(15)->through(function ($tenant) {
            $clientCount = TenantClient::where('tenant_id', $tenant->id)->count();
            $invoiceCount = Invoice::where('tenant_id', $tenant->id)->count();
            $teamMemberCount = TeamMember::where('tenant_id', $tenant->id)->count();
            
            $totalRevenue = Invoice::where('tenant_id', $tenant->id)
                ->where('status', 'paid')
                ->sum('business_amount');

            return [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'owner_name' => $tenant->user?->name ?? 'Unknown',
                'owner_email' => $tenant->user?->email ?? '-',
                'status' => $tenant->status ?? 'active',
                'client_count' => $clientCount,
                'invoice_count' => $invoiceCount,
                'team_count' => $teamMemberCount,
                'revenue' => round($totalRevenue, 2),
                'created_at' => $tenant->created_at?->format('Y-m-d'),
                'user_id' => $tenant->user_id,
            ];
        });

        $stats = [
            'total_tenants' => Tenant::count(),
            'active_tenants' => Tenant::where('status', 'active')->count(),
            'total_revenue' => round(Invoice::where('status', 'paid')->sum('business_amount'), 2),
            'total_team_members' => TeamMember::count(),
        ];

        return Inertia::render('Admin/ERP/Index', [
            'tenants' => $tenants,
            'filters' => $request->only(['search']),
            'stats' => $stats,
        ]);
    }

    /**
     * Show detailed tenant data.
     */
    public function show($id): InertiaResponse
    {
        $tenant = Tenant::with('user')->findOrFail($id);

        $clients = TenantClient::where('tenant_id', $tenant->id)
            ->withCount('invoices')
            ->latest()
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'email' => $c->email ?? '-',
                'phone' => $c->phone ?? '-',
                'currency' => $c->currency?->currency ?? 'USD',
                'invoices_count' => $c->invoices_count,
            ]);

        $invoices = Invoice::where('tenant_id', $tenant->id)
            ->with(['client'])
            ->latest()
            ->limit(30)
            ->get()
            ->map(fn($i) => [
                'id' => $i->id,
                'invoice_number' => $i->invoice_number,
                'client_name' => $i->client?->name ?? 'Unknown',
                'amount' => $i->amount,
                'currency' => $i->currency?->currency ?? 'USD',
                'status' => $i->status,
                'issued_at' => $i->issued_at?->format('Y-m-d'),
            ]);

        $teamMembers = TeamMember::where('tenant_id', $tenant->id)
            ->latest()
            ->get()
            ->map(fn($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'email' => $t->email,
                'role' => $t->role,
                'status' => $t->status,
                'last_login_at' => $t->last_login_at?->format('Y-m-d H:i') ?? '-',
            ]);

        $tasks = ERPTask::where('tenant_id', $tenant->id)
            ->latest()
            ->limit(30)
            ->get()
            ->map(fn($t) => [
                'id' => $t->id,
                'title' => $t->task_name,
                'priority' => $t->priority,
                'status' => $t->status,
                'due_date' => $t->due_date?->format('Y-m-d') ?? 'No limit',
            ]);

        $totalRevenue = Invoice::where('tenant_id', $tenant->id)
            ->where('status', 'paid')
            ->sum('business_amount');

        return Inertia::render('Admin/ERP/Show', [
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'owner_name' => $tenant->user?->name ?? 'Unknown',
                'owner_email' => $tenant->user?->email ?? '-',
                'user_id' => $tenant->user_id,
                'status' => $tenant->status,
                'created_at' => $tenant->created_at?->format('Y-m-d'),
                'revenue' => round($totalRevenue, 2),
            ],
            'clients' => $clients,
            'invoices' => $invoices,
            'teamMembers' => $teamMembers,
            'tasks' => $tasks,
        ]);
    }
}
