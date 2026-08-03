<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\ContractVersion;
use App\Models\Currency;
use App\Models\Project;
use App\Models\User;
use App\Services\AI\ScopePricingEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class AdminQuickContractController extends Controller
{
    protected ScopePricingEngine $pricingEngine;

    public function __construct()
    {
        $this->pricingEngine = new ScopePricingEngine();
    }

    /**
     * Show Quick Scope Pricing & Contract Generator Page.
     */
    public function create()
    {
        return Inertia::render('Admin/Contracts/QuickCreate', [
            'clients'    => User::whereDoesntHave('roles', function ($q) {
                $q->where('name', 'admin');
            })->get(['id', 'name', 'email']),
            'currencies' => Currency::all(),
        ]);
    }

    /**
     * Calculate scope valuation live from text description.
     */
    public function calculate(Request $request)
    {
        $validated = $request->validate([
            'description' => 'required|string|min:5',
            'currency_id' => 'nullable|integer|exists:currencies,id',
        ]);

        $project = new Project([
            'project_name' => mb_strimwidth($validated['description'], 0, 50, '…'),
            'description'  => $validated['description'],
        ]);

        $valuation = $this->pricingEngine->calculateValuation(
            $project,
            [$validated['description']],
            ['currency_id' => $validated['currency_id'] ?? null]
        );

        return response()->json([
            'ok'        => true,
            'valuation' => $valuation,
        ]);
    }

    /**
     * Store Quick Contract & Create Project with Shareable UUID Link.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'description'  => 'required|string|min:5',
            'client_id'    => 'nullable|integer|exists:users,id',
            'currency_id'  => 'nullable|integer|exists:currencies,id',
        ]);

        $description = $validated['description'];
        $currencyId  = $validated['currency_id'] ?? 1;

        // 1. Calculate Two-Level Valuation
        $projectDummy = new Project(['project_name' => mb_strimwidth($description, 0, 50, '…'), 'description' => $description]);
        $valuation = $this->pricingEngine->calculateValuation($projectDummy, [$description], ['currency_id' => $currencyId]);

        $totalUsd       = (float) $valuation['recommended_usd'];
        $convertedAmount = (float) $valuation['converted_amount'];
        $currencyId     = $validated['currency_id'] ?? 1;

        // 2. Create Project
        $projectName = mb_strimwidth($description, 0, 60, '…');
        $project = Project::create([
            'project_name' => $projectName,
            'description'  => $description,
            'user_id'      => $validated['client_id'] ?? null,
            'status'       => 'pending',
            'ai_context'   => [
                'current_stage'      => 'PROPOSAL',
                'current_archetype'  => 'component_based',
                'pending_features'   => array_column($valuation['micro_components'], 'name_ar'),
                'valuation_summary'  => $valuation,
            ],
        ]);

        // 3. Create Contract
        $uuid = (string) Str::uuid();
        $reference = 'CTR-' . strtoupper(Str::random(6));

        $contract = Contract::create([
            'uuid'           => $uuid,
            'project_id'     => $project->id,
            'user_id'        => $validated['client_id'] ?? null,
            'project_name'   => $projectName,
            'description'    => $description,
            'reference'      => $reference,
            'total_amount'   => $totalUsd,
            'deposit_amount' => round($totalUsd * 0.50, 2),
            'currency_id'    => $currencyId,
            'duration'       => $valuation['estimated_days'] . ' أيام عمل (' . $valuation['total_hours'] . ' ساعة)',
            'status'         => 'draft',
            'content'        => [
                'terms'         => 'سداد 50% دفعة أولى عند التوقيع لبدء التنفيذ الفوري، و50% عند التسليم النهائي وتأكيد المعاينة.',
                'duration'      => $valuation['estimated_days'] . ' أيام عمل',
                'key_features'  => array_column($valuation['micro_components'], 'name_ar'),
                'pricing_items' => array_map(fn($c) => [
                    'name'  => $c['name_ar'],
                    'price' => $c['cost_usd'],
                    'hours' => $c['estimated_hours'],
                ], $valuation['micro_components']),
            ],
        ]);

        ContractVersion::create([
            'contract_id'    => $contract->id,
            'version_number' => 1,
            'content'        => $contract->content,
            'total_amount'   => $totalUsd,
            'created_by'     => auth()->id(),
        ]);

        $shareableUrl = url("/contracts/{$uuid}");

        return redirect()->back()->with([
            'success'        => 'تم إنشاء العقد وتوليد الرابط القابل للمشاركة بنجاح!',
            'shareable_url'  => $shareableUrl,
            'contract_uuid'  => $uuid,
            'contract_ref'   => $reference,
        ]);
    }
}
