<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Billing\PlatformContract;
use App\Models\Billing\PlatformProposal;
use App\Services\PriceCalculatorService;
use App\Http\Requests\Admin\PriceCalculator\CalculateAiProposalRequest;
use App\Http\Requests\Admin\PriceCalculator\StoreProposalRequest;
use App\Http\Resources\ProposalResource;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class PriceCalculatorController extends Controller
{
    public function __construct(
        protected PriceCalculatorService $priceCalculatorService
    ) {}
    public function index()
    {
        $proposals = PlatformProposal::latest()->take(10)->get();

        return Inertia::render('Admin/Calculator/Index', [
            // Could load previous proposals here if needed
            'proposals' => ProposalResource::collection($proposals)->resolve()
        ]);
    }

    public function calculateAI(CalculateAiProposalRequest $request)
    {
        try {
            $result = $this->priceCalculatorService->calculateAI($request->validated('project_details'));
            return response()->json($result);
        } catch (\Exception $e) {
            $status = str_contains($e->getMessage(), 'not configured') ? 400 : 500;
            return response()->json(['error' => $e->getMessage()], $status);
        }
    }

    public function saveProposal(StoreProposalRequest $request)
    {
        $this->priceCalculatorService->saveProposal(Auth::id(), $request->validated());

        return redirect()->back()->with('success', 'Proposal saved successfully!');
    }

    public function convertToContract(Request $request, PlatformProposal $proposal)
    {
        $this->priceCalculatorService->convertToContract($proposal);

        return redirect()->route('admin.contracts.index')->with('success', 'Contract generated from proposal!');
    }
}
