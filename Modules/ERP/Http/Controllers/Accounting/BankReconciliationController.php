<?php

namespace Modules\ERP\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\ERP\Services\Accounting\BankReconciliationService;

class BankReconciliationController extends Controller
{
    protected $service;

    public function __construct(BankReconciliationService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        return response()->json($this->service->all());
    }

    // TODO: implement standard CRUD methods
}
