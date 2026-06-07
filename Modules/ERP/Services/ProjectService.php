<?php

namespace Modules\ERP\Services;

use Modules\ERP\Models\Project;
use Modules\ERP\Models\Tenant;
use Modules\ERP\Models\TenantClient;
use Illuminate\Support\Facades\Auth;
use Modules\ERP\Models\Invoice;
use Modules\ERP\Models\InvoiceCost;
use Modules\ERP\Models\ERPTask;
use Modules\ERP\Models\SupportTicket;
use Modules\ERP\Models\Activity;
use Modules\ERP\Models\WalletTransaction;

class ProjectService
{
    public function createProject(array $validated, Tenant $tenant, int $userId): Project
    {
        $client = TenantClient::where('tenant_id', $tenant->id)->findOrFail($validated['client_id']);
        if (!$client->currency_id) {
            throw new \Exception("Client {$client->name} is missing an associated currency relation.");
        }

        $project = Project::create([
            'tenant_id' => $tenant->id,
            'client_id' => $validated['client_id'],
            'name' => $validated['name'],
            'status' => $validated['status'],
            'budget' => $validated['budget'] ?? 0,
            'currency_id' => $client->currency_id,
            'due_date' => $validated['due_date'] ?? null,
            'created_by' => $userId,
        ]);

        ActivityLogger::log(
            'project_created',
            "Project '{$project->name}' was created.",
            $project,
            $project->client_id
        );

        return $project;
    }

    public function updateProject(Project $project, array $validated, Tenant $tenant): Project
    {
        $client = TenantClient::where('tenant_id', $tenant->id)->findOrFail($validated['client_id']);
        if (!$client->currency_id) {
            throw new \Exception("Client {$client->name} is missing an associated currency relation.");
        }

        $project->update([
            'name' => $validated['name'],
            'client_id' => $validated['client_id'],
            'status' => $validated['status'],
            'budget' => $validated['budget'] ?? 0,
            'currency_id' => $client->currency_id,
            'due_date' => $validated['due_date'] ?? null,
        ]);

        ActivityLogger::log(
            'project_updated',
            "Project '{$project->name}' was updated.",
            $project,
            $project->client_id
        );

        return $project;
    }

    public function deleteProject(Project $project): void
    {
        $name = $project->name;
        $project->delete();

        ActivityLogger::log(
            'project_deleted',
            "Project '{$name}' was deleted.",
            null,
            null
        );
    }

    public function getProjectDashboardStats(Project $project): array
    {
        $project->load(['client', 'creator', 'currency']);

        $invoices = Invoice::where('project_id', $project->id)
            ->with(['currency'])
            ->latest()
            ->get();

        $paidInvoices = $invoices->where('status', 'paid');
        $unpaidInvoices = $invoices->whereIn('status', ['sent', 'partial']);

        $totalInvoicedBusiness = (float) $invoices->sum('business_amount');
        $totalPaidBusiness = (float) $paidInvoices->sum('business_amount');
        $totalUnpaidBusiness = (float) $unpaidInvoices->sum('business_amount');

        $invoiceIds = $invoices->pluck('id');
        $expenses = InvoiceCost::whereIn('invoice_id', $invoiceIds)
            ->with(['payer', 'currency'])
            ->latest()
            ->get();

        $totalExpensesBusiness = (float) $expenses->sum('business_amount');
        $netRevenueBusiness = $totalPaidBusiness - $totalExpensesBusiness;

        $transactions = WalletTransaction::where('project_id', $project->id)
            ->with(['creator', 'currency'])
            ->latest()
            ->get();

        $tasks = ERPTask::where('project_id', $project->id)
            ->with(['creator', 'assignee'])
            ->latest()
            ->get();

        $tickets = SupportTicket::where('project_id', $project->id)
            ->with(['assignee', 'creator'])
            ->latest()
            ->get();

        $activities = Activity::with('causer')
            ->where('subject_type', Project::class)
            ->where('subject_id', $project->id)
            ->latest()
            ->limit(10)
            ->get();

        return [
            'project' => $project,
            'invoices' => $invoices,
            'expenses' => $expenses,
            'transactions' => $transactions,
            'tasks' => $tasks,
            'tickets' => $tickets,
            'activities' => $activities,
            'stats' => [
                'paidInvoicesCount' => $paidInvoices->count(),
                'unpaidInvoicesCount' => $unpaidInvoices->count(),
                'totalInvoicesCount' => $invoices->count(),
                'totalInvoiced' => $totalInvoicedBusiness,
                'totalPaid' => $totalPaidBusiness,
                'totalUnpaid' => $totalUnpaidBusiness,
                'totalExpenses' => $totalExpensesBusiness,
                'netRevenue' => $netRevenueBusiness,
            ]
        ];
    }
}
