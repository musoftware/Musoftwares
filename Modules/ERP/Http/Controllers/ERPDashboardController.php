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
use App\Models\Ticket;
use Modules\ERP\Models\Activity;
use Modules\ERP\Models\TenantFile;
use Modules\ERP\Models\TenantNote;
use Modules\ERP\Models\TenantStorageProvider;
use Modules\ERP\Models\WalletTransaction;
use App\Services\ExchangeRateService;
use Inertia\Inertia;
use Carbon\Carbon;

class ERPDashboardController extends Controller
{
    /**
     * ERP workspace dashboard with real operational data.
     * Redirects to onboarding if the tenant workspace isn't initialized yet.
     */
    public function index(\Illuminate\Http\Request $request)
    {
        $user = Auth::user();
        // Resolve tenant for the user, redirect to onboarding if not existing
        $tenant = Tenant::where('user_id', $user->id)->first();
        if (!$tenant) {
            return redirect()->route('erp.onboarding');
        }

        $currency = \App\Models\Currency::find($tenant->base_currency_id);
        $businessCurrency = $currency ? $currency->currency : config('app.business_currency', 'USD');
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
            $limit = $request->query('section') === 'clients' ? 100 : 10;
            $query = TenantClient::where('tenant_id', $tenantId)
                ->with(['wallet', 'currency'])
                ->withCount('invoices');

            if ($request->filled('search') && $request->query('section') === 'clients') {
                $search = $request->query('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                      ->orWhere('email', 'like', '%' . $search . '%')
                      ->orWhere('phone', 'like', '%' . $search . '%');
                });
            }

            $clients = $query->latest()
                ->limit($limit)
                ->get()
                ->map(function ($client) {
                    $unpaid = Invoice::where('client_id', $client->id)
                        ->whereIn('status', ['sent', 'partial'])
                        ->sum('amount');
                    $totalPaid = Invoice::where('client_id', $client->id)
                        ->where('status', 'paid')
                        ->sum('amount');
                    return [
                        'id' => $client->id,
                        'name' => $client->name,
                        'company' => $client->name,
                        'email' => $client->email ?? '-',
                        'phone' => $client->phone ?? '-',
                        'address' => $client->address ?? '-',
                        'currency' => $client->currency?->currency ?? 'USD',
                        'balance' => round($client->wallet?->balance ?? 0, 2),
                        'unpaid' => round($unpaid, 2),
                        'totalPaid' => round($totalPaid, 2),
                        'invoices_count' => $client->invoices_count,
                        'created_at' => $client->created_at?->format('M d, Y'),
                    ];
                });
        }

        // ── Real Invoice List ─────────────────────────────────────
        $invoices = collect();
        if ($tenantId) {
            $invoices = Invoice::with(['tenantClient', 'platformClient'])
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

                $costs = DB::table('erp_invoice_costs')
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

        // ── Real Tasks ─────────────────────────────────────────────
        $tasks = collect();
        if ($tenantId) {
            $tasks = \Modules\ERP\Models\ERPTask::with(['creator', 'assignee'])
                ->where('tenant_id', $tenantId)
                ->where('archived', false)
                ->latest()
                ->get()
                ->map(function ($task) {
                    $category = 'Todo';
                    if ($task->status === 'in_progress') $category = 'In Progress';
                    if ($task->status === 'review') $category = 'In Review';
                    if ($task->status === 'completed') $category = 'Done';
                    
                    return [
                        'id' => $task->id,
                        'title' => $task->task_name,
                        'due' => $task->due_date ? $task->due_date->format('M j, Y') : 'No due date',
                        'assignee' => $task->assignee ? $task->assignee->name : ($task->creator ? $task->creator->name : 'Unassigned'),
                        'priority' => ucfirst($task->priority ?? 'Normal'),
                        'category' => $category,
                    ];
                });
        }

        // ── Growth Metrics ────────────────────────────────────────
        $growthPercent = null;
        if ($tenantId) {
            // M6 fix: capture subMonth once to avoid calling now() twice
            $lastMonthDate = Carbon::now()->subMonth();

            $thisMonth = Invoice::where('tenant_id', $tenantId)
                ->where('status', 'paid')
                ->whereMonth('paid_at', Carbon::now()->month)
                ->whereYear('paid_at', Carbon::now()->year)
                ->sum('business_amount');
            $lastMonth = Invoice::where('tenant_id', $tenantId)
                ->where('status', 'paid')
                ->whereMonth('paid_at', $lastMonthDate->month)
                ->whereYear('paid_at', $lastMonthDate->year)
                ->sum('business_amount');
            if ($lastMonth > 0) {
                $growthPercent = round((($thisMonth - $lastMonth) / $lastMonth) * 100, 1);
            }
        }

        // ── Real Projects List ─────────────────────────────────────
        $projects = collect();
        if ($tenantId) {
            $projects = Project::with(['tenantClient', 'platformClient'])
                ->where('tenant_id', $tenantId)
                ->latest()
                ->limit(10)
                ->get()
                ->map(function ($project) {
                    return [
                        'id' => $project->id,
                        'name' => $project->name,
                        'client' => $project->client?->name ?? 'Unknown',
                        'client_id' => $project->client_id,
                        'status' => $project->status,
                        'budget' => round($project->budget, 2),
                        'deadline' => $project->due_date?->format('Y-m-d') ?? null,
                        'progress' => strtolower($project->status) === 'completed' ? 100 : (strtolower($project->status) === 'active' ? 50 : 0),
                        'leader' => $project->creator?->name ?? '-',
                    ];
                });
        }
        // ── Real Support Tickets ──────────────────────────────────
        $supportTickets = collect();
        if (class_exists(Ticket::class)) {
            $supportTickets = Ticket::with(['user'])->latest()->take(5)->get();
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

        // ── Storage Providers & Documents ────────────────────────────
        $storageProviders = collect();
        $documents = collect();
        if ($tenantId) {
            $storageProviders = TenantStorageProvider::where('tenant_id', $tenantId)->get();
            $documents = TenantFile::with(['uploader', 'storageProvider'])
                ->where('tenant_id', $tenantId)
                ->latest()
                ->get()
                ->map(function ($doc) {
                    return [
                        'id' => $doc->id,
                        'name' => $doc->name,
                        'provider' => $doc->storageProvider?->name ?? 'Local',
                        'size' => round($doc->size / 1024, 2) . ' KB',
                        'tags' => [$doc->folder],
                        'uploadedBy' => $doc->uploader?->name ?? 'Unknown',
                        'date' => $doc->created_at?->format('Y-m-d'),
                    ];
                });
        }

        // ── Workspace Notes ───────────────────────────────────────
        $notes = collect();
        if ($tenantId) {
            $notes = TenantNote::where('tenant_id', $tenantId)
                ->orderByDesc('pinned')
                ->latest()
                ->get()
                ->map(function ($note) {
                    return [
                        'id'       => $note->id,
                        'title'    => $note->title,
                        'content'  => $note->content ?? '',
                        'category' => $note->category,
                        'pinned'   => (bool) $note->pinned,
                        'date'     => $note->updated_at?->format('Y-m-d'),
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

        // ── Transactions (Wallet Ledger) ──────────────────────────
        $transactions = collect();
        $transactionStats = ['totalCredits' => 0, 'totalDebits' => 0, 'netFlow' => 0, 'txnCount' => 0];
        if ($tenantId) {
            $rawTxns = WalletTransaction::with(['creator', 'wallet.tenantClient', 'currency'])
                ->where('tenant_id', $tenantId)
                ->latest()
                ->take(50)
                ->get();

            $transactionStats['txnCount'] = $rawTxns->count();
            $transactionStats['totalCredits'] = round($rawTxns->where('direction', 'credit')->sum('business_amount'), 2);
            $transactionStats['totalDebits'] = round($rawTxns->where('direction', 'debit')->sum('business_amount'), 2);
            $transactionStats['netFlow'] = round($transactionStats['totalCredits'] - $transactionStats['totalDebits'], 2);

            $transactions = $rawTxns->map(function ($txn) use ($businessCurrency) {
                    $title = 'Manual ' . ucfirst($txn->type);
                    if ($txn->reference_type === 'invoice') $title = 'Invoice Settlement';
                    else if ($txn->reference_type === 'withdrawal') $title = 'Withdrawal Settlement';

                    $txnCurrency = $txn->currency?->currency ?? $businessCurrency;
                    $clientCurrency = $txn->wallet?->tenantClient?->currency?->currency ?? $txnCurrency;
                    
                    return [
                        'id' => $txn->id,
                        'reference_id' => '#TXN-' . str_pad($txn->id, 4, '0', STR_PAD_LEFT),
                        'title' => $title,
                        'type' => $txn->type,
                        'note' => $txn->note ?? 'No details provided',
                        'direction' => strtoupper($txn->direction),
                        'amount' => round($txn->amount, 2),
                        'business_amount' => round($txn->business_amount ?? $txn->amount, 2),
                        'currency' => $txnCurrency,
                        'client_currency' => $clientCurrency,
                        'business_currency' => $businessCurrency,
                        'balance_before' => round($txn->balance_before ?? 0, 2),
                        'balance_after' => round($txn->balance_after ?? 0, 2),
                        'reference_type' => $txn->reference_type,
                        'reference_id_raw' => $txn->reference_id,
                        'client_name' => $txn->wallet?->tenantClient?->name ?? 'Unknown',
                        'client_id' => $txn->wallet?->tenantClient?->id,
                        'authorizer' => $txn->creator?->name ?? 'System Core',
                        'date' => $txn->created_at?->format('Y-m-d H:i'),
                    ];
                });
        }

        return Inertia::render('ERP/Dashboard', [
            'tenant' => $tenant,
            'stats' => $stats,
            'clients' => $clients,
            'invoices' => $invoices,
            'chartData' => $chartData,
            'projects' => $projects,
            'supportTickets' => $supportTickets,
            'activityLogs' => $activityLogs,
            'upcomingBookings' => $upcomingBookings,
            'storageProviders' => $storageProviders ?? [],
            'documents' => $documents ?? [],
            'tasks' => $tasks,
            'notes' => $notes,
            'transactions' => $transactions,
            'transactionStats' => $transactionStats,
            'hasMultiCurrency' => $user->hasModuleSubscription('erp-multi-currency'),
            'currencies' => \App\Models\Currency::all(),
            'filters' => $request->only(['search']),
        ]);
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
            'currencies' => \App\Models\Currency::all(),
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
                $tenant = Tenant::create([
                    'user_id' => $user->id,
                    'name' => $request->businessName,
                    'status' => 'active',
                    'base_currency_id' => \App\Models\Currency::where('currency', $request->baseCurrency)->value('id'),
                ]);

                // 2. We no longer set user global currency preference here.
                // The currency is tied strictly to the tenant above.

                // 3. Create first client if provided
                if ($request->clientName) {
                    $clientCurrency = $request->clientCurrency ?: $request->baseCurrency;

                    $client = TenantClient::create([
                        'tenant_id' => $tenant->id,
                        'name' => $request->clientName,
                        'email' => $request->clientEmail,
                        'currency_id' => \App\Models\Currency::where('currency', $clientCurrency)->value('id'),
                    ]);

                    // Auto-create client wallet
                    \Modules\ERP\Models\ClientWallet::firstOrCreate(
                        ['tenant_id' => $tenant->id, 'client_id' => $client->id],
                        ['balance' => 0, 'currency_id' => \App\Models\Currency::where('currency', $clientCurrency)->value('id')]
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
                            'currency_id' => $client->currency_id,
                            'business_amount' => $amount,
                            'business_currency_id' => $tenant->base_currency_id,
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

    public function updateSettings(\Illuminate\Http\Request $request)
    {
        $validated = $request->validate([
            'workspaceName' => 'required|string|max:255',
            'taxRate' => 'nullable|numeric|min:0|max:100',
            'defaultCurrency' => 'nullable|string|size:3',
        ]);

        $user = Auth::user();
        $tenant = Tenant::where('user_id', $user->id)->firstOrFail();

        $tenant->name = $validated['workspaceName'];
        $tenant->save();

        // Update tenant's default currency if provided
        if (isset($validated['defaultCurrency'])) {
            $currency = \App\Models\Currency::where('currency', $validated['defaultCurrency'])->first();
            if ($currency) {
                $tenant->base_currency_id = $currency->id;
                $tenant->save();
            }
        }

        return redirect()->back()->with('success', 'Workspace settings updated successfully.');
    }
}
