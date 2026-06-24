<?php

namespace Modules\ERP\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\ERP\Services\Accounting\FiscalYearService;

class FiscalYearController extends Controller
{
    protected $service;

    public function __construct(FiscalYearService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        return response()->json($this->service->all());
    }

    // TODO: implement standard CRUD methods
}
