<?php

namespace Modules\ERP\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\ContractVersion;
use App\Models\ContractPriceItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class AdminContractController extends Controller
{
    public function index()
    {
        $contracts = Contract::with(['user', 'versions', 'project'])
            ->orderBy('id', 'desc')
            ->paginate(20);

        return Inertia::render('Admin/Contracts/Index', [
            'contracts' => $contracts
        ]);
    }

    public function create()
    {
        $priceItems = ContractPriceItem::all();
        $currencies = \App\Models\Currency::all();
        
        $exchangeRates = [];
        foreach ($currencies as $c1) {
            foreach ($currencies as $c2) {
                $exchangeRates[$c1->id][$c2->id] = \App\Models\CurrenciesExchange::RateToday(1, $c1->id, $c2->id);
            }
        }

        return Inertia::render('Admin/Contracts/Form', [
            'contract' => null,
            'priceItems' => $priceItems,
            'currencies' => $currencies,
            'exchangeRates' => $exchangeRates
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'total_amount' => 'required|numeric|min:0',
            'currency_id' => 'required|integer',
            'duration' => 'nullable|integer|min:1',
            'content' => 'nullable|array',
            'status' => 'nullable|string|in:draft,sent,signed,active,completed'
        ]);

        $contract = DB::transaction(function () use ($validated) {
            $contract = new Contract();
            $contract->uuid = (string) Str::uuid();
            $contract->user_id = Auth::id();
            $contract->project_name = $validated['project_name'];
            $contract->description = $validated['description'] ?? null;
            $contract->total_amount = $validated['total_amount'];
            $contract->currency_id = $validated['currency_id'];
            $contract->duration = $validated['duration'] ?? null;
            $contract->status = $validated['status'] ?? 'draft';
            $contract->content = $validated['content'] ?? [];
            $contract->save();

            ContractVersion::create([
                'contract_id' => $contract->id,
                'user_id' => Auth::id(),
                'description' => $contract->description,
                'total_amount' => $contract->total_amount,
                'content' => $contract->content,
            ]);

            return $contract;
        });

        return redirect()->route('admin.contracts.index')->with('success', __('general.contract_created_successfully'));
    }

    public function edit(Contract $contract)
    {
        $contract->load('versions');
        $priceItems = ContractPriceItem::all();
        $currencies = \App\Models\Currency::all();
        
        $exchangeRates = [];
        foreach ($currencies as $c1) {
            foreach ($currencies as $c2) {
                $exchangeRates[$c1->id][$c2->id] = \App\Models\CurrenciesExchange::RateToday(1, $c1->id, $c2->id);
            }
        }

        return Inertia::render('Admin/Contracts/Form', [
            'contract' => $contract,
            'priceItems' => $priceItems,
            'currencies' => $currencies,
            'exchangeRates' => $exchangeRates
        ]);
    }

    public function update(Request $request, Contract $contract)
    {
        $validated = $request->validate([
            'project_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'total_amount' => 'required|numeric|min:0',
            'currency_id' => 'required|integer',
            'duration' => 'nullable|integer|min:1',
            'content' => 'nullable|array',
            'status' => 'nullable|string|in:draft,sent,signed,active,completed'
        ]);

        DB::transaction(function () use ($validated, $contract) {
            $contract->update([
                'project_name' => $validated['project_name'],
                'description' => $validated['description'] ?? null,
                'total_amount' => $validated['total_amount'],
                'currency_id' => $validated['currency_id'],
                'duration' => $validated['duration'] ?? null,
                'status' => $validated['status'] ?? $contract->status,
                'content' => $validated['content'] ?? [],
            ]);

            ContractVersion::create([
                'contract_id' => $contract->id,
                'user_id' => Auth::id(),
                'description' => $contract->description,
                'total_amount' => $contract->total_amount,
                'content' => $contract->content,
            ]);
        });

        return redirect()->route('admin.contracts.index')->with('success', __('general.contract_updated_successfully'));
    }

    public function show(Contract $contract)
    {
        $contract->load(['versions', 'invoices', 'user']);
        return Inertia::render('Admin/Contracts/Show', [
            'contract' => $contract
        ]);
    }

    public function destroy(Contract $contract)
    {
        $contract->delete();
        return redirect()->route('admin.contracts.index')->with('success', __('general.contract_deleted_successfully'));
    }
}
