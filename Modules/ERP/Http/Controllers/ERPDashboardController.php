<?php

namespace Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\ERP\Models\Invoice;
use Modules\ERP\Models\TenantClient;
use Modules\ERP\Models\RecurringEntry;
use Modules\ERP\Models\Tenant;
use Modules\ERP\Models\Project;
use Modules\ERP\Models\SupportTicket;
use Modules\ERP\Models\Activity;
use Modules\Core\Services\ExchangeRateService;
use Inertia\Inertia;
use Carbon\Carbon;

class ERPDashboardController extends Controller
{
    /**
     * ERP workspace dashboard with real operational data.
     * Redirects to onboarding if the tenant workspace isn't initialized yet.
     */
    public function index()
    {
        $user = Auth::user();
        $businessCurrency = $user->preferred_currency ?? config('app.business_currency', 'USD');

        // Resolve tenant for the user, redirect to onboarding if not existing
        $tenant = Tenant::where('user_id', $user->id)->first();
        if (!$tenant) {
            return redirect()->route('erp.onboarding');
        }
        $tenantId = $tenant->id;

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
                        'currency' => $client->currency ?? 'USD',
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

        // ── Real Projects List ─────────────────────────────────────
        $projects = collect();
        if ($tenantId) {
            $projects = Project::with('client')
                ->where('tenant_id', $tenantId)
                ->latest()
                ->limit(10)
                ->get()
                ->map(function ($project) {
                    return [
                        'id' => $project->id,
                        'name' => $project->name,
                        'client' => $project->client?->name ?? 'Unknown',
                        'status' => $project->status,
                        'budget' => round($project->budget, 2),
                        'deadline' => $project->due_date?->format('Y-m-d') ?? '-',
                        'progress' => $project->status === 'completed' ? 100 : ($project->status === 'in_progress' ? 50 : 0),
                        'leader' => $project->creator?->name ?? '-',
                    ];
                });
        }

        // ── Real Support Tickets ──────────────────────────────────
        $supportTickets = collect();
        if ($tenantId) {
            $supportTickets = SupportTicket::with('client')
                ->where('tenant_id', $tenantId)
                ->latest()
                ->limit(10)
                ->get()
                ->map(function ($ticket) {
                    return [
                        'id' => $ticket->id,
                        'title' => $ticket->subject,
                        'client' => $ticket->client?->name ?? 'Unknown',
                        'priority' => ucfirst($ticket->priority),
                        'status' => ucfirst($ticket->status),
                        'date' => $ticket->created_at?->format('Y-m-d'),
                    ];
                });
        }

        // ── Real Activity Logs ────────────────────────────────────
        $activityLogs = collect();
        if ($tenantId) {
            $activityLogs = Activity::with('causer')
                ->where('tenant_id', $tenantId)
                ->latest()
                ->limit(15)
                ->get()
                ->map(function ($activity) {
                    return [
                        'title' => $activity->action,
                        'time' => $activity->created_at?->diffForHumans(),
                        'description' => $activity->description,
                        'user' => $activity->causer?->name ?? 'System',
                    ];
                });
        }

        $stats = [
            'totalRevenue' => round($totalPaidRevenue, 2),
            'outstandingRevenue' => round($outstandingRevenue, 2),
            'clientCount' => $clientCount,
            'recurringCount' => $recurringCount,
            'growthPercent' => $growthPercent,
            'businessCurrency' => $businessCurrency,
        ];

        // ── Upcoming Bookings ─────────────────────────────────────
        $upcomingBookings = collect();
        if ($tenantId) {
            $upcomingBookings = \Modules\Booking\Models\Booking::with('eventType')
                ->whereHas('eventType', function($q) use ($user) {
                    $q->where('user_id', $user->id);
                })
                ->where('starts_at', '>=', now())
                ->whereIn('status', ['confirmed', 'paid'])
                ->orderBy('starts_at', 'asc')
                ->take(5)
                ->get();
        }

        return Inertia::render('ERP/Dashboard', [
            'stats' => $stats,
            'clients' => $clients,
            'invoices' => $invoices,
            'chartData' => $chartData,
            'projects' => $projects,
            'supportTickets' => $supportTickets,
            'activityLogs' => $activityLogs,
            'upcomingBookings' => $upcomingBookings,
        ]);
    }

    public function storeClient(\Illuminate\Http\Request $request)
    {
        $user = Auth::user();
        $tenant = Tenant::firstOrCreate(
            ['user_id' => $user->id],
            ['name' => $user->name . "'s Workspace", 'status' => 'active']
        );

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'currency' => 'required|string|size:3',
        ]);

        $client = TenantClient::create([
            'tenant_id' => $tenant->id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'address' => $validated['address'],
            'currency' => $validated['currency'] ?? 'USD',
        ]);

        // Auto-create client wallet
        \Modules\ERP\Models\ClientWallet::firstOrCreate(
            ['tenant_id' => $tenant->id, 'client_id' => $client->id],
            ['balance' => 0, 'currency' => $client->currency]
        );

        \Modules\ERP\Services\ActivityLogger::log(
            'client_created',
            "Client '{$client->name}' was added.",
            $client,
            $client->id
        );

        return back()->with('success', 'Client created successfully.');
    }

    public function updateClient(\Illuminate\Http\Request $request, TenantClient $client)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'currency' => 'required|string|size:3',
        ]);

        $client->update($validated);

        \Modules\ERP\Services\ActivityLogger::log(
            'client_updated',
            "Client '{$client->name}' profile was updated.",
            $client,
            $client->id
        );

        return back()->with('success', 'Client updated successfully.');
    }

    public function destroyClient(TenantClient $client)
    {
        $client->delete();
        return back()->with('success', 'Client deleted successfully.');
    }

    /**
     * Show ERP Workspace onboarding setup wizard.
     */
    public function onboarding()
    {
        $user = Auth::user();
        if (Tenant::where('user_id', $user->id)->exists()) {
            return redirect()->route('erp.dashboard');
        }

        return Inertia::render('ERP/Onboarding', [
            'user' => $user,
        ]);
    }

    /**
     * Complete ERP Workspace onboarding setup.
     */
    public function completeOnboarding(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'businessName' => 'required|string|max:255',
            'baseCurrency' => 'required|string|size:3',
            'timezone' => 'required|string',
            'clientName' => 'nullable|string|max:255',
            'clientEmail' => 'nullable|email|max:255',
            'clientCurrency' => 'nullable|string|size:3',
            'invoiceDesc' => 'nullable|string|max:255',
            'invoiceAmount' => 'nullable|numeric|min:0',
        ]);

        $user = Auth::user();

        if (Tenant::where('user_id', $user->id)->exists()) {
            return redirect()->route('erp.dashboard');
        }

        try {
            DB::transaction(function () use ($request, $user) {
                // 1. Create the Tenant
                $tenant = Tenant::create([
                    'user_id' => $user->id,
                    'name' => $request->businessName,
                    'status' => 'active',
                ]);

                // 2. Set user global currency preference
                $user->preferred_currency = $request->baseCurrency;
                $user->save();

                // 3. Create first client if provided
                if ($request->clientName) {
                    $clientCurrency = $request->clientCurrency ?: $request->baseCurrency;

                    $client = TenantClient::create([
                        'tenant_id' => $tenant->id,
                        'name' => $request->clientName,
                        'email' => $request->clientEmail,
                        'currency' => $clientCurrency,
                    ]);

                    // Auto-create client wallet
                    \Modules\ERP\Models\ClientWallet::firstOrCreate(
                        ['tenant_id' => $tenant->id, 'client_id' => $client->id],
                        ['balance' => 0, 'currency' => $clientCurrency]
                    );

                    // 4. Create first invoice if provided
                    if ($request->invoiceDesc && $request->invoiceAmount) {
                        $amount = (float) $request->invoiceAmount;

                        $invoice = Invoice::create([
                            'tenant_id' => $tenant->id,
                            'invoice_number' => 'INV-0001',
                            'client_id' => $client->id,
                            'status' => 'sent',
                            'amount' => $amount,
                            'amount_currency' => $clientCurrency,
                            'business_amount' => $amount,
                            'business_currency' => $request->baseCurrency,
                            'exchange_rate' => 1.0,
                            'exchange_rate_date' => Carbon::now(),
                            'due_date' => Carbon::now()->addDays(14),
                            'issued_at' => Carbon::now(),
                            'notes' => $request->invoiceDesc,
                            'created_by' => $user->id,
                        ]);

                        // Create invoice item
                        \Modules\ERP\Models\InvoiceItem::create([
                            'invoice_id' => $invoice->id,
                            'tenant_id' => $tenant->id,
                            'type' => 'simple',
                            'title' => $request->invoiceDesc,
                            'unit_price' => $amount,
                            'quantity' => 1.0,
                            'total' => $amount,
                        ]);
                    }
                }
            });

            return redirect()->route('erp.dashboard')->with('success', 'Workspace configured successfully! Welcome to your Business OS.');

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('ERP Onboarding wizard failed: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Onboarding setup failed: ' . $e->getMessage()]);
        }
    }
}
