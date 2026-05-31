<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Models\User;
use App\Models\Project;
use App\Services\InvoiceService;
use App\Http\Requests\Admin\Invoice\UpdateInvoiceRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InvoiceController extends Controller
{
    public function __construct(
        protected InvoiceService $invoiceService
    ) {}
    private function applyFilters($query, Request $request)
    {
        if ($request->filled('client_id')) {
            $query->where('user_id', $request->client_id);
        }

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        $filterBy = $request->input('filter_by', 'all');
        $search = $request->input('search');

        if ($filterBy === 'unlinked') {
            $query->whereNull('project_id');
            $filterBy = 'all';
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($filterBy, $search) {
                $decodedSearch = $search;
                if (in_array($filterBy, ['all', 'id']) && class_exists(\App\Helpers\TextHelper::class)) {
                    try {
                        $decodedSearch = \App\Helpers\TextHelper::instance()->crockford_decode2($search);
                    } catch (\Exception $e) {
                        // Ignore
                    }
                }

                if ($filterBy === 'all') {
                    $q->orWhere('id', $decodedSearch)
                      ->orWhere('status', $search)
                      ->orWhereHas('user', function ($uq) use ($search) {
                          $uq->where('name', 'like', '%' . $search . '%');
                      });
                } elseif ($filterBy === 'id') {
                    $q->where('id', $decodedSearch);
                } elseif ($filterBy === 'client_name') {
                    $q->whereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', '%' . $search . '%');
                    });
                } elseif ($filterBy === 'status') {
                    if ($search === 'unpaid_partial') {
                        $q->whereIn('status', ['unpaid', 'partially_paid']);
                    } elseif ($search === 'archived') {
                        $q->where('status', 'cancelled');
                    } else {
                        $q->where('status', $search);
                    }
                } elseif ($filterBy === 'date') {
                    $q->whereDate('created_at', $search);
                }
            });
        }

        return $query;
    }

    private function getStats(Request $request)
    {
        if (!$request->filled('client_id') && !$request->filled('project_id')) {
            return null;
        }

        $query = Invoice::query();
        if ($request->filled('client_id')) {
            $query->where('user_id', $request->client_id);
        }
        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        return [
            'total' => $query->clone()->count(),
            'paid' => $query->clone()->where('status', 'paid')->count(),
            'unpaid' => $query->clone()->where('status', 'unpaid')->count(),
            'partially_paid' => $query->clone()->where('status', 'partially_paid')->count(),
        ];
    }

    /**
     * Display all invoices.
     */
    public function index(Request $request)
    {
        $query = Invoice::with(['user', 'project'])->latest();
        $query = $this->applyFilters($query, $request);

        $invoices = $query->paginate($request->input('per_page', 20))
            ->withQueryString()
            ->through(fn ($invoice) => (new InvoiceResource($invoice))->resolve());

        $projects = $request->filled('client_id') 
            ? \App\Models\Project::whereHas('users', fn($q) => $q->where('users.id', $request->client_id))->get()
            : \App\Models\Project::all();

        return Inertia::render('Admin/Invoices/Index', [
            'invoices' => $invoices,
            'currentTab' => 'all',
            'filters' => $request->only(['client_id', 'project_id', 'search', 'filter_by', 'per_page']),
            'stats' => $this->getStats($request),
            'projects' => $projects,
        ]);
    }

    /**
     * Display unpaid invoices.
     */
    public function unpaid(Request $request)
    {
        $query = Invoice::with(['user', 'project'])->whereIn('status', ['unpaid', 'partially_paid'])->latest();
        $query = $this->applyFilters($query, $request);

        $invoices = $query->paginate($request->input('per_page', 20))
            ->withQueryString()
            ->through(fn ($invoice) => (new InvoiceResource($invoice))->resolve());

        $projects = $request->filled('client_id') 
            ? \App\Models\Project::whereHas('users', fn($q) => $q->where('users.id', $request->client_id))->get()
            : \App\Models\Project::all();

        return Inertia::render('Admin/Invoices/Index', [
            'invoices' => $invoices,
            'currentTab' => 'unpaid',
            'filters' => $request->only(['client_id', 'project_id', 'search', 'filter_by', 'per_page']),
            'stats' => $this->getStats($request),
            'projects' => $projects,
        ]);
    }

    /**
     * Display archived/cancelled invoices.
     */
    public function archive(Request $request)
    {
        $query = Invoice::with(['user', 'project'])->where('status', 'cancelled')->latest();
        $query = $this->applyFilters($query, $request);

        $invoices = $query->paginate($request->input('per_page', 20))
            ->withQueryString()
            ->through(fn ($invoice) => (new InvoiceResource($invoice))->resolve());

        $projects = $request->filled('client_id') 
            ? \App\Models\Project::whereHas('users', fn($q) => $q->where('users.id', $request->client_id))->get()
            : \App\Models\Project::all();

        return Inertia::render('Admin/Invoices/Index', [
            'invoices' => $invoices,
            'currentTab' => 'archive',
            'filters' => $request->only(['client_id', 'project_id', 'search', 'filter_by', 'per_page']),
            'stats' => $this->getStats($request),
            'projects' => $projects,
        ]);
    }

    /**
     * Create a new blank invoice for the given client and redirect to its show page.
     * Mirrors the legacy system behavior: no form, instant creation.
     */
    public function create(Request $request)
    {
        $client = User::find($request->input('client_id'));
        if (!$client) {
            return redirect()->route('admin.invoices.index')
                ->with('error', __('admin.client_not_found'));
        }

        $project = $request->filled('project_id')
            ? Project::where('id', $request->input('project_id'))->first()
            : null;

        try {
            $invoice = Invoice::createInvoice($client, $project, null);
            return redirect()->route('admin.invoices.show', $invoice->id)
                ->with('success', __('admin.invoice_created'));
        } catch (\Exception $e) {
            \Log::error('Invoice creation failed: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', __('admin.invoice_creation_failed'));
        }
    }

    /**
     * Display the specified invoice.
     */
    public function show(Invoice $invoice)
    {
        $invoice->load(['user.projects', 'project', 'items.timers', 'costLines.creditUser']);
        
        return Inertia::render('Admin/Invoices/Show', [
            'invoice' => (new InvoiceResource($invoice))->resolve()
        ]);
    }

    /**
     * Update the invoice items, discount, etc.
     */
    public function update(UpdateInvoiceRequest $request, Invoice $invoice)
    {
        try {
            $this->invoiceService->updateInvoice($invoice, $request->validated());
        } catch (\Exception $e) {
            \Log::error('Invoice update failed: ' . $e->getMessage());
            return redirect()->back()->with('error', __('admin.invoice_update_failed'));
        }

        return redirect()->back()->with('success', __('admin.invoice_updated'));
    }

    /**
     * Mark an invoice as paid manually.
     */
    public function markPaid(Request $request, Invoice $invoice)
    {
        try {
            $this->invoiceService->markPaid($invoice);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', __('admin.invoice_marked_paid'));
    }

    /**
     * Cancel an invoice.
     */
    public function cancel(Request $request, Invoice $invoice)
    {
        try {
            $this->invoiceService->cancelInvoice($invoice);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', __('admin.invoice_cancelled'));
    }

    /**
     * Change invoice status directly.
     */
    public function changeStatus(Request $request, Invoice $invoice)
    {
        $request->validate(['status' => 'required|in:unpaid,partially_paid,paid,cancelled']);
        
        try {
            if ($request->status === 'paid' && $invoice->status !== 'paid') {
                $this->invoiceService->markPaid($invoice);
            } elseif ($request->status === 'cancelled' && $invoice->status !== 'cancelled') {
                $this->invoiceService->cancelInvoice($invoice);
            } else {
                $invoice->update(['status' => $request->status]);
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', __('admin.invoice_status_updated'));
    }

    /**
     * Change invoice job status directly.
     */
    public function changeJobStatus(Request $request, Invoice $invoice)
    {
        $request->validate(['job_status' => 'required|in:pending,processing,done']);
        
        try {
            $invoice->update(['job_status' => $request->job_status]);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', __('admin.job_status_updated'));
    }

    /**
     * Handle bulk actions for invoices.
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|string',
            'invoices' => 'required|array',
            'project_id' => 'nullable|integer'
        ]);

        $action = $request->input('action');
        $invoiceIds = $request->input('invoices');
        $invoices = Invoice::whereIn('id', $invoiceIds)->get();

        if ($invoices->isEmpty()) {
            return redirect()->back()->with('error', __('admin.no_invoices_selected'));
        }

        $whatsapp_invoices_by_user = [];

        foreach ($invoices as $inv) {
            if ($action == 'delete') {
                $inv->delete_with_balance();
            } elseif ($action == 'archive') {
                $inv->archive = '1';
                $inv->save();
            } elseif ($action == 'unarchive') {
                $inv->archive = '0';
                $inv->save();
            } elseif ($action == 'split') {
                if ($inv->status != 'unpaid') {
                    return redirect()->back()->with('error', __('admin.only_unpaid_can_be_split'));
                }
            } elseif ($action == 'merge') {
                if ($inv->status != 'unpaid') {
                    return redirect()->back()->with('error', __('admin.only_unpaid_can_be_merged'));
                }
            } elseif ($action == 'fix_calc') {
                if ($inv->status == 'unpaid') {
                    $inv->unpaid = $inv->total();
                    $inv->save();
                }
            } elseif ($action == 'bill_invoice') {
                if ($inv->status == 'paid') {
                    return redirect()->back()->with('error', __('admin.invoice_already_paid'));
                }
                $client_balance = $inv->user->balance($inv->currency);
                $invoice_total = $inv->unpaid_total();

                if (((float)$client_balance >= (float)$inv->unpaid_total()) && ((float)$inv->unpaid_total() > 0)) {
                    $inv->bill_invoice();
                } else {
                    if (((float)$inv->total() == 0)) {
                        return redirect()->back()->with('error', __('admin.invoice_total_zero'));
                    } else {
                        return redirect()->back()->with('error', __('admin.insufficient_balance'));
                    }
                }
            } elseif ($action == 'change_project') {
                $projectId = $request->input('project_id');
                if (empty($projectId)) {
                    $inv->transfer_to_project(null);
                } else {
                    $exist_project = $inv->user->projects()->find($projectId);
                    if ($exist_project == null) {
                        return redirect()->back()->with('error', __('admin.project_not_associated'));
                    } else {
                        $inv->transfer_to_project($projectId);
                    }
                }
            } elseif ($action == 'convert_to_transaction') {
                if ($inv->status == 'paid') {
                    return redirect()->back()->with('error', __('admin.cannot_convert_paid_invoice'));
                }
                $invoice_total = $inv->unpaid_total();
                if ($invoice_total <= 0) {
                    return redirect()->back()->with('error', __('admin.no_unpaid_amount'));
                }
                $project = $inv->project;
                $inv->user->add_balance(
                    -1 * $invoice_total,
                    'Invoice #' . $inv->id . ' converted to transaction',
                    'used',
                    $inv->currency,
                    $project
                );
                $inv->paid = $inv->total();
                $inv->status = 'paid';
                $inv->save();
            } elseif ($action == 'send_whatsapp_reminder') {
                if ($inv->status != 'unpaid' && $inv->status != 'partially_paid') {
                    return redirect()->back()->with('error', __('admin.only_unpaid_for_whatsapp'));
                }
                $userId = $inv->user_id;
                if (!isset($whatsapp_invoices_by_user[$userId])) {
                    $whatsapp_invoices_by_user[$userId] = [];
                }
                $whatsapp_invoices_by_user[$userId][] = $inv;
            }
        }

        if ($action == 'split') {
            foreach ($invoices as $inv) {
                $items = $inv->items()->get();
                if (count($items) > 1) {
                    $clone = $inv->cloneInvoice();
                    $clone->save();
                    for ($i = 0; $i < count($items) / 2; $i++) {
                        $items[$i]->invoice_id = $clone->id;
                        $items[$i]->save();
                    }
                }
            }
        }

        if ($action == 'merge') {
            $first_invoice = $invoices->first();
            foreach ($invoices as $inv) {
                if ($inv->user_id != $first_invoice->user_id) {
                    return redirect()->back()->with('error', __('admin.different_clients_cant_merge'));
                }
                if ($inv->project_id != $first_invoice->project_id) {
                    return redirect()->back()->with('error', __('admin.different_projects_cant_merge'));
                }
            }

            foreach ($invoices as $inv) {
                if ($inv->id != $first_invoice->id) {
                    foreach ($inv->items()->get() as $invoice_item) {
                        $invoice_item->invoice_id = $first_invoice->id;
                        $invoice_item->save();
                    }
                    $inv->delete();
                }
            }
            if ($first_invoice->status == 'unpaid') {
                $first_invoice->unpaid = $first_invoice->total();
                $first_invoice->save();
            }
        }

        if ($action == 'send_whatsapp_reminder' && !empty($whatsapp_invoices_by_user)) {
            $reminderService = app(\App\Services\WhatsAppNotificationService::class);
            foreach ($whatsapp_invoices_by_user as $userId => $userInvoices) {
                $client = \App\Models\User::find($userId);
                if ($client) {
                    $result = $reminderService->sendInvoiceReminder($client, collect($userInvoices));
                    if (!$result['success']) {
                        return redirect()->back()->with('error', __('admin.whatsapp_reminder_failed', ['name' => $result['client_name']]));
                    }
                }
            }
            return redirect()->back()->with('success', __('admin.whatsapp_reminders_sent'));
        }

        return redirect()->back()->with('success', __('admin.bulk_action_applied'));
    }

    /**
     * Download the invoice as a PDF.
     */
    public function downloadPdf(Invoice $invoice)
    {
        $invoice->loadMissing(['user.projects', 'project', 'items.timers', 'costLines.creditUser']);
        $pdf = \App\Helpers\TextHelper::pdfInvoice($invoice);
        $clientName = $invoice->user ? $invoice->user->name : 'Client';
        return $pdf->download(str_replace(' ', '-', $clientName) . '-' . $invoice->invoice_number . '.pdf');
    }

    /**
     * Print/stream the invoice as a PDF.
     */
    public function printPdf(Invoice $invoice)
    {
        $invoice->loadMissing(['user.projects', 'project', 'items.timers', 'costLines.creditUser']);
        $pdf = \App\Helpers\TextHelper::pdfInvoice($invoice);
        return $pdf->stream();
    }

    /**
     * Notify client about their invoice (mark as published).
     */
    public function notify(Invoice $invoice)
    {
        $invoice->update(['is_published' => 1]);
        return redirect()->back()->with('success', __('admin.notification_sent'));
    }

    /**
     * Record a partial payment on an invoice.
     */
    public function partialPay(Request $request, Invoice $invoice)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

        $amount = (float) $request->amount;
        $unpaidTotal = $invoice->unpaid_total();

        if ($amount > $unpaidTotal) {
            return redirect()->back()->with('error', __('admin.amount_exceeds_unpaid'));
        }

        try {
            if (method_exists($invoice, 'partially_bill_invoice')) {
                $invoice->partially_bill_invoice($amount);
            } else {
                $invoice->paid = ($invoice->paid ?? 0) + $amount;
                $invoice->unpaid = $invoice->total() - $invoice->paid;
                $invoice->status = ($invoice->paid >= $invoice->total()) ? 'paid' : 'partially_paid';
                $invoice->save();
            }
        } catch (\Exception $e) {
            \Log::error('Partial payment failed: ' . $e->getMessage());
            return redirect()->back()->with('error', __('admin.partial_payment_failed'));
        }

        return redirect()->back()->with('success', __('admin.partial_payment_recorded'));
    }

    /**
     * Show timer details for an invoice item.
     */
    public function timerDetails($item_id)
    {
        $item = \App\Models\InvoiceItem::with(['timers', 'invoice.user'])->findOrFail($item_id);
        
        $timers = $item->timers->map(function ($timer) {
            return [
                'id' => $timer->id,
                'start_date' => $timer->start_date,
                'end_date' => $timer->end_date,
                'amount' => (float) $timer->amount,
                'duration_seconds' => $timer->start_date && $timer->end_date
                    ? \Carbon\Carbon::parse($timer->end_date)->diffInSeconds(\Carbon\Carbon::parse($timer->start_date))
                    : 0,
            ];
        });

        $totalSeconds = $timers->sum('duration_seconds');
        $totalBillable = $timers->sum('amount');

        return \Inertia\Inertia::render('Admin/Invoices/TimerDetails', [
            'item' => [
                'id' => $item->id,
                'item_title' => $item->item_title,
                'invoice_id' => $item->invoice_id,
                'invoice_number' => $item->invoice->invoice_number,
                'client_name' => $item->invoice->user ? $item->invoice->user->name : null,
            ],
            'timers' => $timers->values()->all(),
            'total_seconds' => $totalSeconds,
            'total_billable' => $totalBillable,
            'hour_rate' => 25.0, // Default hour rate, can be customized later
        ]);
    }

    public function storeTimerDetails(\Illuminate\Http\Request $request, $item_id)
    {
        $item = \App\Models\InvoiceItem::findOrFail($item_id);
        
        $request->validate([
            'sessions' => 'required|array',
            'sessions.*.start_date' => 'required|date',
            'sessions.*.end_date' => 'required|date',
            'sessions.*.amount' => 'required|numeric',
        ]);

        foreach ($request->sessions as $session) {
            \App\Models\InvoiceItemTimer::create([
                'invoice_item_id' => $item->id,
                'date_start' => \Carbon\Carbon::parse($session['start_date'])->toDateTimeString(),
                'date_end' => \Carbon\Carbon::parse($session['end_date'])->toDateTimeString(),
                'amount' => $session['amount'],
                'project_id' => $item->invoice->project_id ?? null,
            ]);
        }

        return redirect()->back()->with('success', __('admin.timer_sessions_saved'));
    }

    public function destroyTimerDetails($item_id, $timer_id)
    {
        $item = \App\Models\InvoiceItem::findOrFail($item_id);
        $timer = \App\Models\InvoiceItemTimer::where('invoice_item_id', $item->id)->findOrFail($timer_id);
        $timer->delete();

        return redirect()->back()->with('success', __('admin.timer_session_deleted'));
    }
}
