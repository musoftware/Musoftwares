<?php

namespace Modules\ERP\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\ERP\Services\Accounting\BankAccountService;

class BankAccountController extends Controller
{
    protected $service;

    public function __construct(BankAccountService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        return response()->json($this->service->all());
    }

    // TODO: implement standard CRUD methods
}
