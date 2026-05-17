<?php

namespace Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\ERP\Models\Invoice;
use Modules\ERP\Models\InvoiceItem;
use Modules\ERP\Models\InvoiceCost;
use Modules\ERP\Models\TenantClient;
use Modules\Core\Models\Currency;
use Modules\Core\Services\ExchangeRateService;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class InvoiceController extends Controller
{
    protected $exchangeRateService;

    public function __construct(ExchangeRateService $exchangeRateService)
    {
        $this->exchangeRateService = $exchangeRateService;
    }

    public function index(Request $request)
    {
        $query = Invoice::with('client');

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

        // Stats in business currency
        $stats = [
            'total' => Invoice::sum('business_amount'),
            'paid' => Invoice::where('status', 'paid')->sum('business_amount'),
            'pending' => Invoice::where('status', 'sent')->sum('business_amount'),
            'overdue' => Invoice::where('status', 'sent')->where('due_date', '<', now())->sum('business_amount'),
            'business_currency' => config('app.business_currency', 'USD'),
        ];

        return Inertia::render('ERP/Invoices/Index', [
            'invoices' => $invoices,
            'stats' => $stats,
            'filters' => $request->only(['search', 'status', 'currency']),
        ]);
    }

    public function create()
    {
        return Inertia::render('ERP/Invoices/Create', [
            'clients' => TenantClient::all(),
            'currencies' => Currency::where('is_active', true)->get(),
            'business_currency' => config('app.business_currency', 'USD'),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:tenant_clients,id',
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

        return DB::transaction(function () use ($validated, $request) {
            $businessCurrency = config('app.business_currency', 'USD');
            $rate = $this->exchangeRateService->getRate($validated['amount_currency'], $businessCurrency, $validated['issued_at']);

            $subtotal = collect($validated['items'])->sum(fn($i) => $i['unit_price'] * $i['quantity']);
            $discount = $validated['discount_amount'] ?? 0;
            $taxable = $subtotal - $discount;
            $tax = $taxable * (($validated['tax_rate'] ?? 0) / 100);
            $total = $taxable + $tax;

            $invoice = Invoice::create([
                'client_id' => $validated['client_id'],
                'invoice_number' => $validated['invoice_number'],
                'status' => 'draft',
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
                'created_by' => Auth::id(),
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

            return redirect()->route('erp.invoices.show', $invoice->id)
                ->with('success', 'Invoice created successfully.');
        });
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['client.wallet', 'items.timerSessions', 'costs', 'creator']);

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
        $invoice->load(['items', 'costs']);
        return Inertia::render('ERP/Invoices/Edit', [
            'invoice' => $invoice,
            'clients' => TenantClient::all(),
            'currencies' => Currency::where('is_active', true)->get(),
        ]);
    }

    public function update(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:tenant_clients,id',
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

        return DB::transaction(function () use ($validated, $invoice) {
            $businessCurrency = config('app.business_currency', 'USD');
            $rate = $this->exchangeRateService->getRate($validated['amount_currency'], $businessCurrency, $validated['issued_at']);

            $subtotal = collect($validated['items'])->sum(fn($i) => $i['unit_price'] * $i['quantity']);
            $discount = $validated['discount_amount'] ?? 0;
            $taxable = $subtotal - $discount;
            $tax = $taxable * (($validated['tax_rate'] ?? 0) / 100);
            $total = $taxable + $tax;

            $invoice->update([
                'client_id' => $validated['client_id'],
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
        $invoice->update([
            'status' => 'sent',
            'issued_at' => now()
        ]);
        // Trigger notification event here
        return back()->with('success', 'Invoice sent to client.');
    }

    public function markPaid(Invoice $invoice)
    {
        $invoice->update([
            'status' => 'paid',
            'paid_at' => now()
        ]);
        // Record ledger entries and wallet transactions here
        return back()->with('success', 'Invoice marked as paid.');
    }

    public function duplicate(Invoice $invoice)
    {
        $newInvoice = $invoice->replicate(['invoice_number', 'status', 'paid_at']);
        $newInvoice->invoice_number = $invoice->invoice_number . '-COPY';
        $newInvoice->status = 'draft';
        $newInvoice->save();

        foreach ($invoice->items as $item) {
            $newItem = $item->replicate(['invoice_id']);
            $newItem->invoice_id = $newInvoice->id;
            $newItem->save();
        }

        return redirect()->route('erp.invoices.edit', $newInvoice->id)
            ->with('success', 'Invoice duplicated.');
    }

    public function downloadPdf(Invoice $invoice)
    {
        $pdf = Pdf::loadView('erp::invoices.pdf', compact('invoice'));
        return $pdf->download("invoice-{$invoice->invoice_number}.pdf");
    }

    public function destroy(Invoice $invoice)
    {
        $invoice->delete();
        return redirect()->route('erp.invoices.index')
            ->with('success', 'Invoice deleted.');
    }
}
