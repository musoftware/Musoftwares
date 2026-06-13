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
use Modules\ERP\Models\Activity;
use Modules\ERP\Models\TenantFile;
use Modules\ERP\Models\TenantNote;
use Modules\ERP\Models\TenantStorageProvider;
use Modules\ERP\Models\SupportTicket;
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
        if (Auth::guard('erp_team')->check()) {
            $tenant = Auth::guard('erp_team')->user()->tenant;
            $ownerUser = $tenant?->user;
        } else {
            $tenant = Tenant::where('user_id', $user?->id)->first();
            $ownerUser = $user;
        }
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
            $totalPaidRevenue = WalletTransaction::where('tenant_id', $tenantId)
                ->whereIn('type', ['received', 'earned', 'refunded', 'sent'])
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
                ->with(['currency'])
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
                        'currency' => $client->currency,
                        'balance' => round($client->balance ?? 0, 2),
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
            $invoices = Invoice::with(['client', 'currency'])
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
                        'currency' => $invoice->currency,
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

                $sales = WalletTransaction::where('tenant_id', $tenantId)
                    ->whereIn('type', ['received', 'earned', 'refunded', 'sent'])
                    ->whereBetween('created_at', [$monthStart, $monthEnd])
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
        if ($tenantId && $ownerUser && $ownerUser->hasModuleSubscription('erp-tasks')) {
            $tasks = \Modules\ERP\Models\ERPTask::with(['creator', 'assignee', 'client'])
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
                        'due_date' => $task->due_date ? $task->due_date->format('Y-m-d') : null,
                        'assignee' => $task->assignee ? $task->assignee->name : ($task->creator ? $task->creator->name : 'Unassigned'),
                        'priority' => ucfirst($task->priority ?? 'Normal'),
                        'category' => $category,
                        'client_id' => $task->client_id,
                        'client' => $task->client ? ['id' => $task->client->id, 'name' => $task->client->name] : null,
                        'task_description' => $task->task_description,
                    ];
                });
        }

        // ── Growth Metrics ────────────────────────────────────────
        $growthPercent = null;
        if ($tenantId) {
            // M6 fix: capture subMonth once to avoid calling now() twice
            $lastMonthDate = Carbon::now()->subMonth();

            $thisMonth = WalletTransaction::where('tenant_id', $tenantId)
                ->whereIn('type', ['received', 'earned', 'refunded', 'sent'])
                ->whereMonth('created_at', Carbon::now()->month)
                ->whereYear('created_at', Carbon::now()->year)
                ->sum('business_amount');
            $lastMonth = WalletTransaction::where('tenant_id', $tenantId)
                ->whereIn('type', ['received', 'earned', 'refunded', 'sent'])
                ->whereMonth('created_at', $lastMonthDate->month)
                ->whereYear('created_at', $lastMonthDate->year)
                ->sum('business_amount');
            if ($lastMonth > 0) {
                $growthPercent = round((($thisMonth - $lastMonth) / $lastMonth) * 100, 1);
            }
        }

        // ── Real Projects List ─────────────────────────────────────
        $projects = collect();
        if ($tenantId && $ownerUser && $ownerUser->hasModuleSubscription('erp-projects')) {
            $projects = Project::with(['client'])
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
        // ── Real ERP Support Tickets ──────────────────────────────
        $supportTickets = collect();
        if ($tenantId) {
            $supportTickets = SupportTicket::with(['client'])
                ->where('tenant_id', $tenantId)
                ->latest()
                ->take(5)
                ->get()
                ->map(function ($ticket) {
                    return [
                        'id'         => $ticket->id,
                        'subject'    => $ticket->subject,
                        'status'     => $ticket->status,
                        'priority'   => $ticket->priority,
                        'client'     => $ticket->client ? ['id' => $ticket->client->id, 'name' => $ticket->client->name] : null,
                        'created_at' => $ticket->created_at?->format('Y-m-d'),
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
        
        // ── Real Expenses List ─────────────────────────────────────
        $expenses = collect();
        if ($tenantId) {
            $expenses = \Modules\ERP\Models\Expense::latest()
                ->get()
                ->map(function ($expense) {
                    return [
                        'id' => $expense->id,
                        'title' => $expense->title,
                        'amount' => number_format($expense->amount, 2, '.', ''),
                        'category' => $expense->category ?? 'General',
                        'date' => $expense->date ? $expense->date->format('Y-m-d') : ($expense->created_at ? $expense->created_at->format('Y-m-d') : ''),
                        'description' => $expense->description ?? '-',
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
                ->whereHas('eventType', function($q) use ($ownerUser) {
                    $q->where('user_id', $ownerUser ? $ownerUser->id : null);
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
            $rawTxns = WalletTransaction::with(['creator', 'client', 'currency'])
                ->where('tenant_id', $tenantId)
                ->latest()
                ->take(50)
                ->get();

            $transactionStats['txnCount'] = $rawTxns->count();
            // Per user request: earned is a client bonus and should not be calculated as business income.
            $transactionStats['totalCredits'] = round($rawTxns->whereIn('type', ['received'])->sum('business_amount'), 2);
            $transactionStats['totalDebits'] = round(abs($rawTxns->whereIn('type', ['refunded', 'sent'])->sum('business_amount')), 2);
            $transactionStats['netFlow'] = round($transactionStats['totalCredits'] - $transactionStats['totalDebits'], 2);

            $transactions = $rawTxns->map(function ($txn) use ($businessCurrency) {
                    $title = ucfirst($txn->type);
                    if ($txn->reference_type === 'invoice') $title = __('erp.invoice_settlement');
                    else if ($txn->reference_type === 'withdrawal') $title = __('erp.withdrawal_settlement');
                    else if ($txn->reference_type === 'manual_receive') $title = __('erp.manual_receive');
                    else if ($txn->reference_type === 'manual_send') $title = __('erp.manual_send');
                    else if ($txn->reference_type === 'manual_refund') $title = __('erp.manual_refund');

                    $txnCurrency = $txn->currency;
                    $clientCurrency = $txn->client?->currency;
                    
                    return [
                        'id' => $txn->id,
                        'reference_id' => '#TXN-' . str_pad($txn->id, 4, '0', STR_PAD_LEFT),
                        'title' => $title,
                        'type' => $txn->type,
                        'note' => $txn->note ?? __('erp.no_details'),
                        'amount' => round($txn->amount, 2),
                        'business_amount' => round($txn->business_amount ?? $txn->amount, 2),
                        'currency' => $txnCurrency,
                        'client_currency' => $clientCurrency,
                        'business_currency' => $businessCurrency,
                        'reference_type' => $txn->reference_type,
                        'reference_id_raw' => $txn->reference_id,
                        'client_name' => $txn->client?->name ?? __('erp.unknown'),
                        'client_id' => $txn->client?->id,
                        'authorizer' => $txn->creator?->name ?? 'System',
                        'date' => $txn->created_at?->format('Y-m-d H:i'),
                    ];
                });
        }
        
        // ── Real Branches List ─────────────────────────────────────
        $branches = collect();
        if ($tenantId && $ownerUser && $ownerUser->hasModuleSubscription('erp-multi-branch')) {
            $branches = \Modules\ERP\Models\Branch::where('tenant_id', $tenantId)->latest()->get()->map(function($b) {
                return [
                    'id' => $b->id,
                    'name' => $b->name,
                    'type' => $b->type,
                    'timezone' => $b->timezone,
                    'status' => $b->status,
                    'created_at' => $b->created_at?->format('Y-m-d')
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
            'expenses' => $expenses,
            'supportTickets' => $supportTickets,
            'activityLogs' => $activityLogs,
            'upcomingBookings' => $upcomingBookings,
            'storageProviders' => $storageProviders ?? [],
            'documents' => $documents ?? [],
            'tasks' => $tasks,
            'notes' => $notes,
            'transactions' => $transactions,
            'transactionStats' => $transactionStats,
            'branches' => $branches,

            'hasMultiCurrency' => $ownerUser ? $ownerUser->hasModuleSubscription('erp-multi-currency') : false,
            'hasMultiBranch' => $ownerUser ? $ownerUser->hasModuleSubscription('erp-multi-branch') : false,
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
        
        if (!$user->hasModuleSubscription('erp')) {
            return redirect()->route('subscriptions.plans')->with('error', __('erp.subscription_required_for_erp'));
        }

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

        if (!$user->hasModuleSubscription('erp')) {
            return redirect()->route('subscriptions.plans')->with('error', __('erp.subscription_required_for_erp'));
        }

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


                    // 4. Create first invoice if provided
                    if ($request->invoiceDesc && $request->invoiceAmount) {
                        $amount = number_format($request->invoiceAmount, 2, '.', '');

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

            return redirect()->route('erp.dashboard')->with('success', __('erp.workspace_configured_success'));

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('ERP Onboarding wizard failed: ' . $e->getMessage());
            return back()->withErrors(['error' => __('erp.onboarding_failed')]);
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
            if (!$user->hasModuleSubscription('erp-multi-currency')) {
                throw new \Exception(__('errors.multi_currency_addon_required'));
            }

            $currency = \App\Models\Currency::where('currency', $validated['defaultCurrency'])->first();
            if ($currency) {
                $tenant->base_currency_id = $currency->id;
                $tenant->save();
            }
        }

        return redirect()->back()->with('success', __('erp.workspace_settings_updated_success'));
    }
}
