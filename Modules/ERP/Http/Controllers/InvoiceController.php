<?php

namespace Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\ERP\Models\Invoice;
use Inertia\Inertia;

class InvoiceController extends Controller
{
    public function index()
    {
        $invoices = Invoice::with('client')->paginate(15);
        return Inertia::render('ERP/Invoices/Index', ['invoices' => $invoices]);
    }
}
