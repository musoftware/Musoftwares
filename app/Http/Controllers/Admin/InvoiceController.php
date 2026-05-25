<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Services\InvoiceService;
use App\Http\Requests\Admin\Invoice\UpdateInvoiceRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InvoiceController extends Controller
{
    public function __construct(
        protected InvoiceService $invoiceService
    ) {}
    /**
     * Display all invoices.
     */
    public function index()
    {
        $invoices = Invoice::with(['user', 'project'])
            ->latest()
            ->paginate(20)
            ->through(fn ($invoice) => (new InvoiceResource($invoice))->resolve());

        return Inertia::render('Admin/Invoices/Index', [
            'invoices' => $invoices,
            'currentTab' => 'all',
        ]);
    }

    /**
     * Display unpaid invoices.
     */
    public function unpaid()
    {
        $invoices = Invoice::with(['user', 'project'])
            ->whereIn('status', ['unpaid', 'partially_paid'])
            ->latest()
            ->paginate(20)
            ->through(fn ($invoice) => (new InvoiceResource($invoice))->resolve());

        return Inertia::render('Admin/Invoices/Index', [
            'invoices' => $invoices,
            'currentTab' => 'unpaid',
        ]);
    }

    /**
     * Display archived/cancelled invoices.
     */
    public function archive()
    {
        $invoices = Invoice::with(['user', 'project'])
            ->whereIn('status', ['cancelled'])
            ->latest()
            ->paginate(20)
            ->through(fn ($invoice) => (new InvoiceResource($invoice))->resolve());

        return Inertia::render('Admin/Invoices/Index', [
            'invoices' => $invoices,
            'currentTab' => 'archive',
        ]);
    }

    /**
     * Display the specified invoice.
     */
    public function show(Invoice $invoice)
    {
        $invoice->load(['user.projects', 'project', 'items.timers', 'costLines.creditUser']);
        
        return Inertia::render('Admin/Invoices/Show', [
            'invoice' => new InvoiceResource($invoice)
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
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', 'Invoice updated successfully.');
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

        return redirect()->back()->with('success', 'Invoice marked as paid.');
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

        return redirect()->back()->with('success', 'Invoice cancelled successfully.');
    }
}
