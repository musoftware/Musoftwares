<?php

namespace Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Core\Models\Invoice;
use Modules\Core\Models\InvoiceItem;
use Modules\Core\Models\InvoiceCost;
use Modules\ERP\Models\WalletTransaction;
use Modules\ERP\Models\TenantClient;
use Modules\Core\Models\Currency;
use Modules\Core\Services\ExchangeRateService;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Modules\ERP\Models\Tenant;
use Modules\Core\Services\ActivityService;
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
        $query = Invoice::with(['tenantClient', 'platformClient'])->where('tenant_id', $tenant->id);

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
            $query->where('amount_currency', $request->currency);
        }

        $invoices = $query->latest()->paginate(15)->withQueryString();

        // Stats scoped to this tenant only
        $businessCurrency = Auth::user()->preferred_currency ?? config('app.business_currency', 'USD');
        $stats = [
            'total'             => Invoice::where('tenant_id', $tenant->id)->sum('business_amount'),
            'paid'              => Invoice::where('tenant_id', $tenant->id)->where('status', 'paid')->sum('business_amount'),
            'pending'           => Invoice::where('tenant_id', $tenant->id)->where('status', 'sent')->sum('business_amount'),
            'overdue'           => Invoice::where('tenant_id', $tenant->id)->where('status', 'sent')->where('due_date', '<', now())->sum('business_amount'),
            'business_currency' => $businessCurrency,
        ];

        return Inertia::render('ERP/Invoices/Index', [
            'invoices' => $invoices,
            'stats'    => $stats,
            'filters'  => $request->only(['search', 'status', 'currency']),
        ]);
    }

    public function create()
    {
        $tenant           = $this->resolveTenant();
        $businessCurrency = Auth::user()->preferred_currency ?? config('app.business_currency', 'USD');

        return Inertia::render('ERP/Invoices/Create', [
            'clients'           => TenantClient::where('tenant_id', $tenant->id)->get(),
            'projects'          => \Modules\Core\Models\Project::where('tenant_id', $tenant->id)->get(),
            'currencies'        => Currency::where('is_active', true)->get(),
            'business_currency' => $businessCurrency,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:tenant_clients,id',
            'project_id' => 'nullable|exists:projects,id',
            'invoice_number' => 'required|string',
            'issued_at' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issued_at',
            'amount_currency' => 'required|string|size:3',
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

        // Ensure client belongs to this tenant
        $client = TenantClient::where('tenant_id', $tenant->id)
            ->findOrFail($validated['client_id']);

        return DB::transaction(function () use ($validated, $tenant, $client) {
            $businessCurrency = Auth::user()->preferred_currency ?? config('app.business_currency', 'USD');
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
                'amount_currency'   => $validated['amount_currency'],
                'business_amount'   => $total * $rate,
                'business_currency' => $businessCurrency,
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
                        'amount_currency' => $validated['amount_currency'],
                        'business_amount' => $costData['amount'] * $costRate,
                        'business_currency' => $businessCurrency,
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
                ->with('success', 'Invoice created successfully.');
        });
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['client.wallet', 'items.timerSessions', 'costs', 'creator', 'project']);

        // This is a simplified timeline, in real app it might be a separate table
        $timeline = [
            ['event' => 'Created', 'time' => $invoice->created_at, 'user' => $invoice->creator?->name],
        ];
        if ($invoice->issued_at) $timeline[] = ['event' => 'Sent', 'time' => $invoice->issued_at, 'user' => 'System'];
        if ($invoice->paid_at) $timeline[] = ['event' => 'Paid', 'time' => $invoice->paid_at, 'user' => 'Client'];

        return Inertia::render('ERP/Invoices/Show', [
            'invoice' => $invoice,
            'timeline' => $timeline,
            'referral_earnings' => \Modules\ERP\Models\ReferralEarning::with('referrer')
                ->where('invoice_id', $invoice->id)
                ->get(),
        ]);
    }

    public function edit(Invoice $invoice)
    {
        // Guard M2: prevent editing finalised invoices
        if (in_array($invoice->status, ['paid', 'cancelled', 'refunded'])) {
            return redirect()->route('erp.invoices.show', $invoice->id)
                ->with('error', 'A ' . $invoice->status . ' invoice cannot be edited.');
        }

        $tenant = $this->resolveTenant();

        // Ensure this invoice belongs to the current tenant
        if ($invoice->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access to invoice.');
        }

        $invoice->load(['items', 'costs']);
        return Inertia::render('ERP/Invoices/Edit', [
            'invoice'           => $invoice,
            'clients'           => TenantClient::where('tenant_id', $tenant->id)->get(),
            'projects'          => \Modules\Core\Models\Project::where('tenant_id', $tenant->id)->get(),
            'currencies'        => Currency::where('is_active', true)->get(),
            'business_currency' => Auth::user()->preferred_currency ?? config('app.business_currency', 'USD'),
        ]);
    }

    public function update(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:tenant_clients,id',
            'project_id' => 'nullable|exists:projects,id',
            'issued_at' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issued_at',
            'amount_currency' => 'required|string|size:3',
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
            return back()->withErrors(['status' => 'A ' . $invoice->status . ' invoice cannot be edited.']);
        }

        $tenant = $this->resolveTenant();
        if ($invoice->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access to invoice.');
        }

        return DB::transaction(function () use ($validated, $invoice, $tenant) {
            $businessCurrency = Auth::user()->preferred_currency ?? config('app.business_currency', 'USD');
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
                'amount_currency' => $validated['amount_currency'],
                'business_amount' => $total * $rate,
                'business_currency' => $businessCurrency,
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
                            'amount_currency' => $validated['amount_currency'],
                            'business_amount' => $costData['amount'] * $costRate,
                            'business_currency' => $businessCurrency,
                            'exchange_rate' => $costRate,
                            'exchange_rate_date' => $validated['issued_at'],
                            'payment_status' => $costData['payment_status'] ?? 'unpaid',
                        ]
                    );
                }
            }

            return redirect()->route('erp.invoices.show', $invoice->id)
                ->with('success', 'Invoice updated successfully.');
        });
    }

    public function send(Invoice $invoice)
    {
        if ($invoice->status !== 'draft') {
            return back()->withErrors(['status' => 'Only draft invoices can be sent.']);
        }

        $invoice->update([
            'status' => 'sent',
            'issued_at' => now()
        ]);

        // Create a wallet transaction record for the issued invoice
        $client = $invoice->client;
        if ($client) {
            $wallet = $client->wallet;
            if ($wallet) {
                WalletTransaction::create([
                    'tenant_id' => $invoice->tenant_id,
                    'wallet_id' => $wallet->id,
                    'type' => 'invoice_issued',
                    'direction' => 'debit',
                    'amount' => $invoice->amount,
                    'amount_currency' => $invoice->amount_currency,
                    'business_amount' => $invoice->business_amount,
                    'business_currency' => $invoice->business_currency ?? 'USD',
                    'exchange_rate' => $invoice->exchange_rate,
                    'exchange_rate_date' => $invoice->exchange_rate_date ?? now()->toDateString(),
                    'balance_before' => $wallet->balance,
                    'balance_after' => $wallet->balance, // No actual balance change on issue
                    'reference_type' => Invoice::class,
                    'reference_id' => $invoice->id,
                    'note' => 'Invoice #' . $invoice->invoice_number . ' issued',
                    'created_by' => Auth::id(),
                ]);
            }
        }

        return back()->with('success', 'Invoice sent to client.');
    }

    /**
     * Mark an invoice as fully paid from client wallet.
     * Recovered from old project: Invoice::bill_invoice()
     */
    public function markPaid(Invoice $invoice)
    {
        $result = $invoice->billInvoice();

        if (!$result['ok']) {
            return back()->withErrors(['payment' => $result['message']]);
        }

        event(new InvoicePaid($invoice));

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
            ->with('success', 'Invoice duplicated as draft.');
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
            return back()->withErrors(['status' => 'A paid invoice cannot be deleted.']);
        }

        $invoice->delete();
        return redirect()->route('erp.invoices.index')
            ->with('success', 'Invoice deleted.');
    }
}
