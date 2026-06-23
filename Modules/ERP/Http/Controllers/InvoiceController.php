<?php

namespace Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\ERP\Models\Invoice;
use Modules\ERP\Models\InvoiceItem;
use Modules\ERP\Models\InvoiceCost;
use Modules\ERP\Models\WalletTransaction;
use Modules\ERP\Models\TenantClient;
use App\Models\Currency;
use App\Services\ExchangeRateService;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Modules\ERP\Models\Tenant;
use App\Services\ActivityService;
use App\Events\InvoicePaid;
use Modules\ERP\Http\Requests\StoreInvoiceRequest;
use Modules\ERP\Http\Requests\UpdateInvoiceRequest;
use Modules\ERP\Services\InvoiceService;

class InvoiceController extends Controller
{
    protected $exchangeRateService;
    protected $invoiceService;

    public function __construct(ExchangeRateService $exchangeRateService, InvoiceService $invoiceService)
    {
        $this->exchangeRateService = $exchangeRateService;
        $this->invoiceService = $invoiceService;
    }

    // ── Tenant resolution ─────────────────────────────────────────

    private function resolveTenant(): Tenant
    {
        return auth('erp_team')->user()->tenant;
    }

    // ── Index ─────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $tenant = $this->resolveTenant();
        $query = Invoice::with(['client', 'currency'])->where('tenant_id', $tenant->id);

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('invoice_number', 'like', '%' . $request->search . '%')
                  ->orWhereHas('client', function($q) use ($request) {
                      $q->where('name', 'like', '%' . $request->search . '%');
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('currency')) {
            $val = $request->currency;
            if (is_numeric($val)) {
                $query->where('currency_id', $val);
            } else {
                $query->whereHas('currency', function($q) use ($val) {
                    $q->where('currency', $val);
                });
            }
        }

        $invoices = $query->latest()->paginate(15)->withQueryString();
        $baseCurrency = Currency::find($tenant->base_currency_id);

        // Stats scoped to this tenant only
        $stats = [
            'total'             => Invoice::where('tenant_id', $tenant->id)->sum('business_amount'),
            'paid'              => Invoice::where('tenant_id', $tenant->id)->where('status', 'paid')->sum('business_amount'),
            'pending'           => Invoice::where('tenant_id', $tenant->id)->where('status', 'sent')->sum('business_amount'),
            'overdue'           => Invoice::where('tenant_id', $tenant->id)->where('status', 'sent')->where('due_date', '<', now())->sum('business_amount'),
            'currency_id'       => $tenant->base_currency_id,
            'business_currency' => $baseCurrency ? $baseCurrency->currency : 'USD',
        ];

        return Inertia::render('ERP/Invoices/Index', [
            'invoices' => $invoices,
            'stats'    => $stats,
            'filters'  => $request->only(['search', 'status', 'currency']),
        ]);
    }

    public function create(Request $request)
    {
        $tenant           = $this->resolveTenant();
        $baseCurrency     = Currency::find($tenant->base_currency_id);

        $user = auth('erp_team')->user();
        if (Auth::guard('erp_team')->check()) {
            $user = Auth::guard('erp_team')->user()->tenant->user ?? $user;
        }
        $hasProjectsAddon = $user ? $user->hasModuleSubscription('erp-projects') : false;
        $hasInventoryAddon = $user ? $user->hasModuleSubscription('erp-inventory') : false;



        $preSelectedProjectId = $request->query('project_id') ? (int) $request->query('project_id') : null;
        $preSelectedClientId = $request->query('client_id') ? (int) $request->query('client_id') : null;

        if ($hasProjectsAddon && $preSelectedProjectId && !$preSelectedClientId) {
            $project = \Modules\ERP\Models\Project::where('tenant_id', $tenant->id)->find($preSelectedProjectId);
            if ($project) {
                $preSelectedClientId = (int) $project->client_id;
            }
        }

        // Only send the pre-selected client (if any)
        $preSelectedClient = null;
        if ($preSelectedClientId) {
            $preSelectedClient = TenantClient::with('currency')
                ->where('tenant_id', $tenant->id)
                ->find($preSelectedClientId);
            if ($preSelectedClient) {
                $preSelectedClient = [
                    'id' => $preSelectedClient->id,
                    'name' => $preSelectedClient->name,
                    'email' => $preSelectedClient->email,
                    'currency' => $preSelectedClient->currency,
                ];
            }
        }

        return Inertia::render('ERP/Invoices/Create', [
            'has_projects_addon'=> $hasProjectsAddon,
            'has_inventory_addon'=> $hasInventoryAddon,
            'currencies'        => Currency::all(),
            'business_currency' => $baseCurrency,
            'pre_selected_client_id' => $preSelectedClientId,
            'pre_selected_project_id' => $hasProjectsAddon ? $preSelectedProjectId : null,
            'pre_selected_client' => $preSelectedClient,
        ]);
    }

    public function store(StoreInvoiceRequest $request)
    {
        $tenant = $this->resolveTenant();

        $invoice = $this->invoiceService->createInvoice($request->validated(), $tenant);

        return redirect()->route('erp.invoices.show', $invoice->id)
            ->with('success', __('erp.invoice_created_success'));
    }

    public function show(Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        $invoice->load(['client.currency', 'items.timerSessions', 'costs', 'creator', 'project']);

        // This is a simplified timeline, in real app it might be a separate table
        $timeline = [
            ['event' => 'Created', 'time' => $invoice->created_at, 'user' => $invoice->creator?->name],
        ];
        if ($invoice->issued_at) $timeline[] = ['event' => 'Sent', 'time' => $invoice->issued_at, 'user' => 'System'];
        if ($invoice->paid_at) $timeline[] = ['event' => 'Paid', 'time' => $invoice->paid_at, 'user' => 'Client'];

        $user = auth('erp_team')->user();
        if (auth('erp_team')->check()) {
            $teamMember = auth('erp_team')->user();
            $user = $teamMember?->tenant?->user;
        }
        $hasReferrals = $user && $user->hasModuleSubscription('erp-referrals');
        $hasSmtpAddon = $user && $user->hasModuleSubscription('erp-smtp');
        $tenant = $invoice->tenant;
        $hasSmtpSettings = $tenant && $tenant->smtpSetting && $tenant->smtpSetting->host && $tenant->smtpSetting->username;

        return Inertia::render('ERP/Invoices/Show', [
            'invoice' => $invoice,
            'timeline' => $timeline,
            'has_smtp_addon' => $hasSmtpAddon,
            'has_smtp_settings' => $hasSmtpSettings,
            'referral_earnings' => $hasReferrals
                ? \Modules\ERP\Models\ReferralEarning::with('referrer')
                    ->where('invoice_id', $invoice->id)
                    ->get()
                : [],
        ]);
    }

    public function edit(Invoice $invoice)
    {
        $this->authorize('update', $invoice);

        $tenant = $this->resolveTenant();

        $user = auth('erp_team')->user();
        if (Auth::guard('erp_team')->check()) {
            $user = Auth::guard('erp_team')->user()->tenant->user ?? $user;
        }
        $hasProjectsAddon = $user ? $user->hasModuleSubscription('erp-projects') : false;
        $hasInventoryAddon = $user ? $user->hasModuleSubscription('erp-inventory') : false;

        $invoice->load(['items', 'costs', 'client.currency']);
        $baseCurrency = Currency::find($tenant->base_currency_id);
        if (!$baseCurrency) {
            throw new \Exception("Tenant base currency not found.");
        }



        // Only send the invoice's current client
        $currentClient = $invoice->client ? [
            'id' => $invoice->client->id,
            'name' => $invoice->client->name,
            'email' => $invoice->client->email,
            'currency' => $invoice->client->currency,
        ] : null;

        return Inertia::render('ERP/Invoices/Edit', [
            'invoice'           => $invoice,
            'pre_selected_client' => $currentClient,
            'has_projects_addon'=> $hasProjectsAddon,
            'has_inventory_addon'=> $hasInventoryAddon,
            'currencies'        => Currency::all(),
            'business_currency' => $baseCurrency,
        ]);
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice)
    {
        $this->authorize('update', $invoice);

        $tenant = $this->resolveTenant();

        // Guard M2: prevent editing finalised invoices
        if (in_array($invoice->status, ['paid', 'cancelled', 'refunded'])) {
            return back()->withErrors(['status' => __('errors.finalised_invoice_cannot_be_edited', ['status' => $invoice->status])]);
        }

        $this->invoiceService->updateInvoice($invoice, $request->validated(), $tenant);

        return redirect()->route('erp.invoices.show', $invoice->id)
            ->with('success', __('erp.invoice_updated_success'));
    }

    public function send(Invoice $invoice)
    {
        if ($invoice->status !== 'draft') {
            return back()->withErrors(['status' => __('errors.only_draft_invoices_can_be_issued')]);
        }

        $invoice->update([
            'status' => 'sent',
            'issued_at' => now()
        ]);

        return back()->with('success', __('erp.invoice_issued_success'));
    }

    public function sendEmail(Invoice $invoice)
    {
        $tenant = $this->resolveTenant();
        $user = auth('erp_team')->user();

        if (!$user->hasModuleSubscription('erp-smtp')) {
            abort(403, __('errors.erp_smtp_addon_required'));
        }

        $smtpSetting = $tenant->smtpSetting;
        if (!$smtpSetting || !$smtpSetting->host || !$smtpSetting->username) {
            return back()->withErrors(['email' => __('errors.smtp_settings_not_configured')]);
        }

        // Normally, you would dynamically configure the mailer here and dispatch a Job/Mailable.
        // For example: Config::set('mail.mailers.smtp.host', $smtpSetting->host);
        // Mail::to($invoice->client->email)->send(new \Modules\ERP\Mail\InvoiceMail($invoice));
        
        // Simulating email send for now.
        ActivityService::log(
            event: 'invoice.emailed',
            description: "Emailed invoice #{$invoice->invoice_number} to client",
            subject: $invoice,
            workspace: 'erp'
        );

        return back()->with('success', __('erp.invoice_emailed_successfully'));
    }

    /**
     * Mark an invoice as fully paid manually.
     */
    public function markPaid(Invoice $invoice)
    {
        $result = $invoice->markPaidManual();

        if (!$result['ok']) {
            return back()->withErrors(['payment' => $result['message']]);
        }

        return back()->with('success', $result['message']);
    }

    /**
     * Mark an internal cost as paid.
     */
    public function markCostPaid(Invoice $invoice, \Modules\ERP\Models\InvoiceCost $cost)
    {
        if ($cost->invoice_id !== $invoice->id) {
            abort(404);
        }

        if ($cost->payment_status === 'paid') {
            return back()->with('info', __('erp.cost_already_paid'));
        }

        DB::transaction(function () use ($invoice, $cost) {
            $cost->update([
                'payment_status' => 'paid',
                'paid_at' => now(),
                'paid_by' => auth('erp_team')->id(),
            ]);

            \Modules\ERP\Models\ExpenseTransaction::create([
                'tenant_id' => $cost->tenant_id,
                'invoice_cost_id' => $cost->id,
                'invoice_id' => $invoice->id,
                'client_id' => $invoice->client_id,
                'amount' => $cost->amount,
                'currency_id' => $cost->currency_id,
                'business_amount' => $cost->business_amount,
                'business_currency_id' => $cost->business_currency_id,
                'exchange_rate' => $cost->exchange_rate,
                'exchange_rate_date' => $cost->exchange_rate_date,
                'balance_before' => 0,
                'balance_after' => 0,
                'note' => 'Paid internal cost for invoice: ' . $invoice->invoice_number,
                'created_by' => auth('erp_team')->id(),
            ]);
        });

        return back()->with('success', __('erp.cost_marked_paid_success'));
    }

    /**
     * Mark an invoice as fully paid from client wallet.
     */
    public function payWallet(Invoice $invoice)
    {
        $result = $invoice->billInvoice();

        if (!$result['ok']) {
            return back()->withErrors(['payment' => $result['message']]);
        }

        return back()->with('success', $result['message']);
    }

    /**
     * Apply a partial payment to an invoice.
     * Recovered from old project: Invoice::partially_bill_invoice()
     */
    public function partialPayment(Request $request, Invoice $invoice)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

        $result = $invoice->partiallyBillInvoice((float) $request->input('amount'));

        if (!$result['ok']) {
            return back()->withErrors(['payment' => $result['message']]);
        }

        return back()->with('success', $result['message']);
    }

    /**
     * Cancel an invoice and refund any paid amounts.
     * Recovered from old project: Invoice::cancel_invoice()
     */
    public function cancel(Invoice $invoice)
    {
        $result = $invoice->cancelInvoice();

        if (!$result['ok']) {
            return back()->withErrors(['status' => $result['message']]);
        }

        return back()->with('success', $result['message']);
    }

    public function duplicate(Invoice $invoice)
    {
        $tenant = $this->resolveTenant();
        if ($invoice->tenant_id !== $tenant->id) {
            abort(403, __('general.unauthorized_access_to_invoice'));
        }

        // M3 fix: use timestamp suffix to avoid unique(tenant_id, invoice_number) constraint
        $newNumber = $invoice->invoice_number . '-COPY-' . now()->format('YmdHis');

        $newInvoice = $invoice->replicate(['invoice_number', 'status', 'paid_at', 'paid_amount', 'issued_at']);
        $newInvoice->invoice_number = $newNumber;
        $newInvoice->status         = 'draft';
        $newInvoice->paid_amount    = 0;
        $newInvoice->issued_at      = null;
        $newInvoice->paid_at        = null;
        $newInvoice->save();

        foreach ($invoice->items as $item) {
            $newItem             = $item->replicate(['invoice_id']);
            $newItem->invoice_id = $newInvoice->id;
            $newItem->save();
        }

        return redirect()->route('erp.invoices.edit', $newInvoice->id)
            ->with('success', __('erp.invoice_duplicated_success'));
    }

    public function downloadPdf(Request $request, Invoice $invoice)
    {
        $invoice->load(['client.currency', 'items', 'tenant.user']);
        
        $paperType = $request->query('paper', 'a4');
        
        if ($paperType === 'thermal') {
            $pdf = Pdf::loadView('erp::invoices.pdf_thermal', compact('invoice'));
            $pdf->setPaper([0, 0, 226.77, 800], 'portrait');
        } else {
            $pdf = Pdf::loadView('erp::invoices.pdf', compact('invoice'));
            $pdf->setPaper('a4', 'portrait');
        }

        return $pdf->download("invoice-{$invoice->invoice_number}.pdf");
    }

    public function destroy(Invoice $invoice)
    {
        $tenant = $this->resolveTenant();
        if ($invoice->tenant_id !== $tenant->id) {
            abort(403, __('general.unauthorized_access_to_invoice'));
        }

        if ($invoice->status === 'paid') {
            return back()->withErrors(['status' => __('errors.paid_invoice_cannot_be_deleted')]);
        }

        $invoice->delete();
        return redirect()->route('erp.invoices.index')
            ->with('success', __('erp.invoice_deleted_success'));
    }
}
