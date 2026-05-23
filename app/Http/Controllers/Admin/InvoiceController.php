<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InvoiceController extends Controller
{
    /**
     * Display all invoices.
     */
    public function index()
    {
        $invoices = Invoice::with('user')
            ->latest()
            ->paginate(20);

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
        $invoices = Invoice::with('user')
            ->whereIn('status', ['unpaid', 'partially_paid'])
            ->latest()
            ->paginate(20);

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
        $invoices = Invoice::with('user')
            ->whereIn('status', ['cancelled'])
            ->latest()
            ->paginate(20);

        return Inertia::render('Admin/Invoices/Index', [
            'invoices' => $invoices,
            'currentTab' => 'archive',
        ]);
    }

    /**
     * Mark an invoice as paid manually.
     */
    public function markPaid(Request $request, Invoice $invoice)
    {
        if ($invoice->status === 'paid') {
            return redirect()->back()->with('error', 'Invoice is already paid.');
        }

        $invoice->mark_as_paid();

        return redirect()->back()->with('success', 'Invoice marked as paid.');
    }

    /**
     * Cancel an invoice.
     */
    public function cancel(Request $request, Invoice $invoice)
    {
        if ($invoice->status === 'cancelled') {
            return redirect()->back()->with('error', 'Invoice is already cancelled.');
        }

        $invoice->cancel_invoice();

        return redirect()->back()->with('success', 'Invoice cancelled successfully.');
    }
}
