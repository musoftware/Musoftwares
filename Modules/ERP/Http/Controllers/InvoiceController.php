<?php

namespace Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\ERP\Models\Invoice;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceController extends Controller
{
    public function index()
    {
        $invoices = Invoice::with('client')->paginate(15);
        return Inertia::render('ERP/Invoices/Index', ['invoices' => $invoices]);
    }

    public function pdf(Invoice $invoice)
    {
        $pdf = Pdf::loadView('erp::invoices.pdf', compact('invoice'));
        return $pdf->download("invoice-{$invoice->invoice_number}.pdf");
    }
}
