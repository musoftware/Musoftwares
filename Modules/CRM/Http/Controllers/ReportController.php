<?php

namespace Modules\CRM\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\CRM\Models\Lead;
use Modules\CRM\Models\Customer;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function index()
    {
        // Simple KPIs for the MVP
        $kpis = [
            'total_leads' => Lead::count(),
            'total_customers' => Customer::count(),
            'total_value' => Customer::sum('total_value'),
            'conversion_rate' => Lead::count() > 0 ? round((Customer::count() / Lead::count()) * 100, 2) : 0,
        ];

        return Inertia::render('CRM/Reports/Index', [
            'kpis' => $kpis,
        ]);
    }
}
