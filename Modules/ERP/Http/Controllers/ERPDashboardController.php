<?php

namespace Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\ERP\Models\Invoice;
use Modules\ERP\Models\TenantClient;
use Modules\ERP\Models\RecurringEntry;
use Modules\ERP\Models\Tenant;
use Modules\Core\Services\ExchangeRateService;
use Inertia\Inertia;
use Carbon\Carbon;

class ERPDashboardController extends Controller
{
    /**
     * ERP workspace dashboard with real operational data.
     * Recovered from old project: DashboardController::adminDashboard() + adminDashboardStats()
     * Modernized for new multi-tenant ERP architecture.
     */
    public function index()
    {
        $user = Auth::user();
        $businessCurrency = config('app.business_currency', 'USD');

        // Resolve tenant for the user
        $tenant = Tenant::where('user_id', $user->id)->first();
        $tenantId = $tenant?->id;

        // ── Revenue Statistics ─────────────────────────────────────
        $totalPaidRevenue = 0;
        $outstandingRevenue = 0;
        $clientCount = 0;
        $recurringCount = 0;

        if ($tenantId) {
            $totalPaidRevenue = Invoice::where('tenant_id', $tenantId)
                ->where('status', 'paid')
                ->sum('business_amount');

            $outstandingRevenue = Invoice::where('tenant_id', $tenantId)
                ->whereIn('status', ['sent', 'partial'])
                ->sum('business_amount');

            $clientCount = TenantClient::where('tenant_id', $tenantId)->count();

            $recurringCount = RecurringEntry::where('tenant_id', $tenantId)
                ->where('status', 'active')
                ->count();
        }

        // ── Real Client List ──────────────────────────────────────
        $clients = collect();
        if ($tenantId) {
            $clients = TenantClient::where('tenant_id', $tenantId)
                ->withCount('invoices')
                ->limit(10)
                ->get()
                ->map(function ($client) {
                    $totalInvoiced = Invoice::where('client_id', $client->id)->sum('amount');
                    $totalPaid = Invoice::where('client_id', $client->id)->where('status', 'paid')->sum('amount');
                    return [
                        'id' => $client->id,
                        'name' => $client->name,
                        'company' => $client->name,
                        'email' => $client->email ?? '-',
                        'phone' => $client->phone ?? '-',
                        'address' => $client->address ?? '-',
                        'totalInvoiced' => round($totalInvoiced, 2),
                        'totalPaid' => round($totalPaid, 2),
                    ];
                });
        }

        // ── Real Invoice List ─────────────────────────────────────
        $invoices = collect();
        if ($tenantId) {
            $invoices = Invoice::with('client')
                ->where('tenant_id', $tenantId)
                ->whereIn('status', ['draft', 'sent', 'partial'])
                ->latest()
                ->limit(10)
                ->get()
                ->map(function ($invoice) {
                    return [
                        'id' => $invoice->id,
                        'invoiceNumber' => $invoice->invoice_number,
                        'clientName' => $invoice->client?->name ?? 'Unknown',
                        'amount' => round($invoice->amount, 2),
                        'currency' => $invoice->amount_currency ?? 'USD',
                        'issuedDate' => $invoice->issued_at?->format('Y-m-d'),
                        'dueDate' => $invoice->due_date?->format('Y-m-d'),
                        'status' => $invoice->status,
                        'project' => $invoice->notes ?? '-',
                    ];
                });
        }

        // ── Monthly Revenue Chart Data ────────────────────────────
        $chartData = [];
        if ($tenantId) {
            $startOfYear = Carbon::now()->startOfYear();
            for ($m = 0; $m < min(Carbon::now()->month, 12); $m++) {
                $monthStart = $startOfYear->copy()->addMonths($m)->startOfMonth();
                $monthEnd = $monthStart->copy()->endOfMonth();
                $monthName = $monthStart->format('M');

                $sales = Invoice::where('tenant_id', $tenantId)
                    ->where('status', 'paid')
                    ->whereBetween('paid_at', [$monthStart, $monthEnd])
                    ->sum('business_amount');

                $costs = DB::table('invoice_costs')
                    ->where('tenant_id', $tenantId)
                    ->whereBetween('created_at', [$monthStart, $monthEnd])
                    ->sum('business_amount');

                $chartData[] = [
                    'name' => $monthName,
                    'Sales' => round($sales, 2),
                    'Costs' => round($costs, 2),
                ];
            }
        }

        // Fallback: if no data yet, provide empty chart structure
        if (empty($chartData)) {
            $chartData = [
                ['name' => Carbon::now()->format('M'), 'Sales' => 0, 'Costs' => 0],
            ];
        }

        // ── Growth Metrics ────────────────────────────────────────
        $growthPercent = null;
        if ($tenantId) {
            $thisMonth = Invoice::where('tenant_id', $tenantId)
                ->where('status', 'paid')
                ->whereMonth('paid_at', Carbon::now()->month)
                ->sum('business_amount');
            $lastMonth = Invoice::where('tenant_id', $tenantId)
                ->where('status', 'paid')
                ->whereMonth('paid_at', Carbon::now()->subMonth()->month)
                ->whereYear('paid_at', Carbon::now()->subMonth()->year)
                ->sum('business_amount');
            if ($lastMonth > 0) {
                $growthPercent = round((($thisMonth - $lastMonth) / $lastMonth) * 100, 1);
            }
        }

        $stats = [
            'totalRevenue' => round($totalPaidRevenue, 2),
            'outstandingRevenue' => round($outstandingRevenue, 2),
            'clientCount' => $clientCount,
            'recurringCount' => $recurringCount,
            'growthPercent' => $growthPercent,
            'businessCurrency' => $businessCurrency,
        ];

        return Inertia::render('ERP/Dashboard', [
            'stats' => $stats,
            'clients' => $clients,
            'invoices' => $invoices,
            'chartData' => $chartData,
        ]);
    }
}
