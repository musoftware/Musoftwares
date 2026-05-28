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

class InvoiceController extends Controller
{
    protected $exchangeRateService;

    public function __construct(ExchangeRateService $exchangeRateService)
    {
        $this->exchangeRateService = $exchangeRateService;
    }

    // ── Tenant resolution ─────────────────────────────────────────

    private function resolveTenant(): Tenant
    {
        return Tenant::where('user_id', Auth::id())->firstOrFail();
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

        $user = Auth::user();
        if (Auth::guard('erp_team')->check()) {
            $user = Auth::guard('erp_team')->user()->tenant->user ?? $user;
        }
        $hasProjectsAddon = $user ? $user->hasModuleSubscription('erp-projects') : false;

        $clients = TenantClient::with('currency')
            ->where('tenant_id', $tenant->id)
            ->get();

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
                    'currency_code' => $preSelectedClient->currency?->currency ?? 'USD',
                ];
            }
        }

        return Inertia::render('ERP/Invoices/Create', [
            'clients'           => $clients,
            'pre_selected_client' => $preSelectedClient,
            'projects'          => $hasProjectsAddon ? \Modules\ERP\Models\Project::where('tenant_id', $tenant->id)->get() : [],
            'has_projects_addon'=> $hasProjectsAddon,
            'currencies'        => Currency::all(),
            'business_currency' => $baseCurrency ? $baseCurrency->currency : 'USD',
            'pre_selected_client_id' => $preSelectedClientId,
            'pre_selected_project_id' => $hasProjectsAddon ? $preSelectedProjectId : null,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:erp_tenant_clients,id',
            'project_id' => 'nullable|exists:erp_projects,id',
            'invoice_number' => 'required|string',
            'issued_at' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issued_at',
            'amount_currency' => 'nullable|string|size:3',
            'items' => 'required|array|min:1',
            'items.*.type' => 'required|in:simple,quantity,timer',
            'items.*.title' => 'required|string',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.quantity' => 'required|numeric|min:0',
            'costs' => 'nullable|array',
            'discount_amount' => 'nullable|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $tenant = $this->resolveTenant();

        $user = Auth::user();
        if (Auth::guard('erp_team')->check()) {
            $user = Auth::guard('erp_team')->user()->tenant->user ?? $user;
        }
        if (!$user || !$user->hasModuleSubscription('erp-projects')) {
            $validated['project_id'] = null;
        }

        // Validate uniqueness of invoice_number under the current tenant
        $exists = Invoice::where('tenant_id', $tenant->id)
            ->where('invoice_number', $validated['invoice_number'])
            ->exists();

        if ($exists) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'invoice_number' => 'The invoice number has already been taken for this tenant.'
            ]);
        }

        // Ensure client belongs to this tenant
        $client = TenantClient::with('currency')->where('tenant_id', $tenant->id)
            ->findOrFail($validated['client_id']);

        $validated['amount_currency'] = $client->currency ? $client->currency->currency : 'USD';

        return DB::transaction(function () use ($validated, $tenant, $client) {
            $currency = \App\Models\Currency::find($tenant->base_currency_id);
            $businessCurrency = $currency ? $currency->currency : 'USD';
            $rate = $this->exchangeRateService->getRate($validated['amount_currency'], $businessCurrency, $validated['issued_at']);

            $subtotal = collect($validated['items'])->sum(fn($i) => $i['unit_price'] * $i['quantity']);
            $discount = $validated['discount_amount'] ?? 0;
            $taxable  = $subtotal - $discount;
            $tax      = $taxable * (($validated['tax_rate'] ?? 0) / 100);
            $total    = $taxable + $tax;

            $invoice = Invoice::create([
                'tenant_id'         => $tenant->id,   // FIX H1: was missing
                'client_id'         => $client->id,
                'project_id'        => $validated['project_id'] ?? null,
                'invoice_number'    => $validated['invoice_number'],
                'status'            => 'draft',
                'paid_amount'       => 0,
                'amount'            => $total,
                'currency_id'       => $client->currency_id,
                'business_amount'   => $total * $rate,
                'exchange_rate'     => $rate,
                'exchange_rate_date'=> $validated['issued_at'],
                'discount_amount'   => $discount,
                'tax_rate'          => $validated['tax_rate'] ?? 0,
                'tax_amount'        => $tax,
                'issued_at'         => $validated['issued_at'],
                'due_date'          => $validated['due_date'],
                'notes'             => $validated['notes'],
                'created_by'        => Auth::id(),
            ]);

            foreach ($validated['items'] as $index => $itemData) {
                $invoice->items()->create([
                    'tenant_id' => $invoice->tenant_id,
                    'type' => $itemData['type'],
                    'title' => $itemData['title'],
                    'description' => $itemData['description'] ?? null,
                    'unit_price' => $itemData['unit_price'],
                    'quantity' => $itemData['quantity'],
                    'total' => $itemData['unit_price'] * $itemData['quantity'],
                    'sort_order' => $index,
                ]);
            }

            if (!empty($validated['costs'])) {
                foreach ($validated['costs'] as $costData) {
                    $costRate = $this->exchangeRateService->getRate($validated['amount_currency'], $businessCurrency, $validated['issued_at']);
                    $invoice->costs()->create([
                        'tenant_id' => $invoice->tenant_id,
                        'title' => $costData['title'],
                        'amount' => $costData['amount'],
                        'currency_id' => $client->currency_id,
                        'business_amount' => $costData['amount'] * $costRate,
                        'business_currency_id' => $tenant->base_currency_id,
                        'exchange_rate' => $costRate,
                        'exchange_rate_date' => $validated['issued_at'],
                        'payment_status' => 'unpaid',
                    ]);
                }
            }

            ActivityService::log(
                event: 'invoice.created',
                description: "Created invoice #{$invoice->invoice_number}",
                subject: $invoice,
                workspace: 'erp'
            );

            return redirect()->route('erp.invoices.show', $invoice->id)
                ->with('success', __('erp.invoice_created_success'));
        });
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['client.currency', 'items.timerSessions', 'costs', 'creator', 'project']);

        // This is a simplified timeline, in real app it might be a separate table
        $timeline = [
            ['event' => 'Created', 'time' => $invoice->created_at, 'user' => $invoice->creator?->name],
        ];
        if ($invoice->issued_at) $timeline[] = ['event' => 'Sent', 'time' => $invoice->issued_at, 'user' => 'System'];
        if ($invoice->paid_at) $timeline[] = ['event' => 'Paid', 'time' => $invoice->paid_at, 'user' => 'Client'];

        $user = Auth::user();
        if (auth('erp_team')->check()) {
            $user = auth('erp_team')->user()?->tenant?->user;
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
        // Guard M2: prevent editing finalised invoices
        if (in_array($invoice->status, ['paid', 'cancelled', 'refunded'])) {
            return redirect()->route('erp.invoices.show', $invoice->id)
                ->with('error', __('errors.finalised_invoice_cannot_be_edited', ['status' => $invoice->status]));
        }

        $tenant = $this->resolveTenant();

        // Ensure this invoice belongs to the current tenant
        if ($invoice->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access to invoice.');
        }

        $user = Auth::user();
        if (Auth::guard('erp_team')->check()) {
            $user = Auth::guard('erp_team')->user()->tenant->user ?? $user;
        }
        $hasProjectsAddon = $user ? $user->hasModuleSubscription('erp-projects') : false;

        $invoice->load(['items', 'costs', 'client.currency']);
        $baseCurrency = Currency::find($tenant->base_currency_id);

        $clients = TenantClient::with('currency')
            ->where('tenant_id', $tenant->id)
            ->get();

        // Only send the invoice's current client
        $currentClient = $invoice->client ? [
            'id' => $invoice->client->id,
            'name' => $invoice->client->name,
            'email' => $invoice->client->email,
            'currency_code' => $invoice->client->currency?->currency ?? 'USD',
        ] : null;

        return Inertia::render('ERP/Invoices/Edit', [
            'invoice'           => $invoice,
            'clients'           => $clients,
            'pre_selected_client' => $currentClient,
            'projects'          => $hasProjectsAddon ? \Modules\ERP\Models\Project::where('tenant_id', $tenant->id)->get() : [],
            'has_projects_addon'=> $hasProjectsAddon,
            'currencies'        => Currency::all(),
            'business_currency' => $baseCurrency ? $baseCurrency->currency : 'USD',
        ]);
    }

    public function update(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:erp_tenant_clients,id',
            'project_id' => 'nullable|exists:erp_projects,id',
            'issued_at' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issued_at',
            'amount_currency' => 'nullable|string|size:3',
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable',
            'items.*.type' => 'required|in:simple,quantity,timer',
            'items.*.title' => 'required|string',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.quantity' => 'required|numeric|min:0',
            'costs' => 'nullable|array',
            'discount_amount' => 'nullable|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        // Guard M2: prevent editing finalised invoices
        if (in_array($invoice->status, ['paid', 'cancelled', 'refunded'])) {
            return back()->withErrors(['status' => __('errors.finalised_invoice_cannot_be_edited', ['status' => $invoice->status])]);
        }

        $tenant = $this->resolveTenant();
        if ($invoice->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access to invoice.');
        }

        $user = Auth::user();
        if (Auth::guard('erp_team')->check()) {
            $user = Auth::guard('erp_team')->user()->tenant->user ?? $user;
        }
        if (!$user || !$user->hasModuleSubscription('erp-projects')) {
            $validated['project_id'] = null;
        }

        $client = TenantClient::with('currency')->where('tenant_id', $tenant->id)->findOrFail($validated['client_id']);
        $validated['amount_currency'] = $client->currency ? $client->currency->currency : 'USD';

        return DB::transaction(function () use ($validated, $invoice, $tenant, $client) {
            $currency = \App\Models\Currency::find($tenant->base_currency_id);
            $businessCurrency = $currency ? $currency->currency : 'USD';
            $rate = $this->exchangeRateService->getRate($validated['amount_currency'], $businessCurrency, $validated['issued_at']);

            $subtotal = collect($validated['items'])->sum(fn($i) => $i['unit_price'] * $i['quantity']);
            $discount = $validated['discount_amount'] ?? 0;
            $taxable = $subtotal - $discount;
            $tax = $taxable * (($validated['tax_rate'] ?? 0) / 100);
            $total = $taxable + $tax;

            $invoice->update([
                'client_id' => $validated['client_id'],
                'project_id' => $validated['project_id'] ?? null,
                'amount' => $total,
                'currency_id' => $client->currency_id,
                'business_amount' => $total * $rate,
                'exchange_rate' => $rate,
                'exchange_rate_date' => $validated['issued_at'],
                'discount_amount' => $discount,
                'tax_rate' => $validated['tax_rate'] ?? 0,
                'tax_amount' => $tax,
                'issued_at' => $validated['issued_at'],
                'due_date' => $validated['due_date'],
                'notes' => $validated['notes'],
            ]);

            // Non-destructive sync for items to preserve timer sessions
            $itemIds = collect($validated['items'])->pluck('id')->filter()->toArray();
            $invoice->items()->whereNotIn('id', $itemIds)->delete();

            foreach ($validated['items'] as $index => $itemData) {
                $invoice->items()->updateOrCreate(
                    ['id' => $itemData['id'] ?? null],
                    [
                        'tenant_id' => $invoice->tenant_id,
                        'type' => $itemData['type'],
                        'title' => $itemData['title'],
                        'description' => $itemData['description'] ?? null,
                        'unit_price' => $itemData['unit_price'],
                        'quantity' => $itemData['quantity'],
                        'total' => $itemData['unit_price'] * $itemData['quantity'],
                        'sort_order' => $index,
                    ]
                );
            }

            // Sync costs
            $costIds = collect($validated['costs'] ?? [])->pluck('id')->filter()->toArray();
            $invoice->costs()->whereNotIn('id', $costIds)->delete();

            if (!empty($validated['costs'])) {
                foreach ($validated['costs'] as $costData) {
                    $costRate = $this->exchangeRateService->getRate($validated['amount_currency'], $businessCurrency, $validated['issued_at']);
                    $invoice->costs()->updateOrCreate(
                        ['id' => $costData['id'] ?? null],
                        [
                            'tenant_id' => $invoice->tenant_id,
                            'title' => $costData['title'],
                            'amount' => $costData['amount'],
                            'currency_id' => $client->currency_id,
                            'business_amount' => $costData['amount'] * $costRate,
                            'business_currency_id' => $tenant->base_currency_id,
                            'exchange_rate' => $costRate,
                            'exchange_rate_date' => $validated['issued_at'],
                            'payment_status' => $costData['payment_status'] ?? 'unpaid',
                        ]
                    );
                }
            }

            return redirect()->route('erp.invoices.show', $invoice->id)
                ->with('success', __('erp.invoice_updated_success'));
        });
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

        // Create a wallet transaction record for the issued invoice
        $client = $invoice->client;
        if ($client) {
            WalletTransaction::create([
                'tenant_id' => $invoice->tenant_id,
                'client_id' => $client->id,
                'type' => 'invoice_issued',
                'direction' => 'debit',
                'amount' => $invoice->amount,
                'currency_id' => $invoice->currency_id,
                'business_amount' => $invoice->business_amount,
                'business_currency_id' => $invoice->tenant->base_currency_id,
                'exchange_rate' => $invoice->exchange_rate,
                'exchange_rate_date' => $invoice->exchange_rate_date ?? now()->toDateString(),
                'reference_type' => Invoice::class,
                'reference_id' => $invoice->id,
                'note' => 'Invoice #' . $invoice->invoice_number . ' issued',
                'created_by' => Auth::id(),
            ]);
        }

        return back()->with('success', __('erp.invoice_issued_success'));
    }

    public function sendEmail(Invoice $invoice)
    {
        $tenant = $this->resolveTenant();
        $user = Auth::user();

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
            abort(403, 'Unauthorized access to invoice.');
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

    public function downloadPdf(Invoice $invoice)
    {
        $pdf = Pdf::loadView('erp::invoices.pdf', compact('invoice'));
        return $pdf->download("invoice-{$invoice->invoice_number}.pdf");
    }

    public function destroy(Invoice $invoice)
    {
        $tenant = $this->resolveTenant();
        if ($invoice->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access to invoice.');
        }

        if ($invoice->status === 'paid') {
            return back()->withErrors(['status' => __('errors.paid_invoice_cannot_be_deleted')]);
        }

        $invoice->delete();
        return redirect()->route('erp.invoices.index')
            ->with('success', __('erp.invoice_deleted_success'));
    }
}
