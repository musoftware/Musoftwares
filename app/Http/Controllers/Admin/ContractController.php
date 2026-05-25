<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Services\ContractService;
use App\Http\Requests\Admin\Contract\UpdateContractStatusRequest;
use App\Http\Resources\ContractResource;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ContractController extends Controller
{
    public function __construct(
        protected ContractService $contractService
    ) {}

    public function create()
    {
        return Inertia::render('Admin/Contracts/Create');
    }

    public function index(Request $request)
    {
        $status = $request->query('status', 'all');
        $query = Contract::with('user');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $contracts = $query->latest()->paginate(20)->through(fn($c) => clone (new ContractResource($c))->resolve());

        return Inertia::render('Admin/Contracts/Index', [
            'contracts' => $contracts,
            'currentTab' => $status,
        ]);
    }
    public function updateStatus(UpdateContractStatusRequest $request, Contract $contract)
    {
        $this->contractService->updateStatus($contract, $request->validated('status'));

        return redirect()->back()->with('success', 'Contract status updated.');
    }
    public function destroy(Contract $contract)
    {
        $this->contractService->deleteContract($contract);

        return redirect()->back()->with('success', 'Contract deleted successfully.');
    }
}
