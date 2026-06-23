<?php

namespace Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Modules\ERP\Models\Tenant;
use Modules\ERP\Models\TeamMember;
use Modules\ERP\Models\PayrollContract;
use Modules\ERP\Models\Payslip;
use Modules\ERP\Models\PayslipItem;
use Modules\ERP\Models\Expense;
use Modules\ERP\Models\PaymentMethod;
use Modules\ERP\Models\WalletTransaction;

class PayrollController extends Controller
{
    protected function resolveTenant()
    {
        $user = auth('erp_team')->user();
        if (auth('erp_team')->check()) {
            $teamMember = auth('erp_team')->user();
            $user = $teamMember?->tenant?->user;
        }
        return auth('erp_team')->user()->tenant;
    }

    protected function checkAddon()
    {
        $user = auth('erp_team')->user();
        if (auth('erp_team')->check()) {
            $teamMember = auth('erp_team')->user();
            $user = $teamMember?->tenant?->user;
        }
        if (!$user || !$user->hasModuleSubscription('erp-payroll')) {
            abort(403, __('erp.upgrade_to_enable_payroll_system'));
        }
        return $user;
    }

    public function index(Request $request): InertiaResponse
    {
        $this->checkAddon();
        $tenant = $this->resolveTenant();

        // 1. Get all active team members with their contracts
        $members = TeamMember::where('tenant_id', $tenant->id)
            ->where('status', 'active')
            ->get();
            
        $contracts = PayrollContract::where('tenant_id', $tenant->id)->get()->keyBy('member_id');
        
        $membersWithContracts = $members->map(function ($member) use ($contracts, $tenant) {
            $contract = $contracts->get($member->id);
            return [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
                'role' => $member->role,
                'contract' => $contract ? [
                    'id' => $contract->id,
                    'base_salary' => $contract->base_salary,
                    'currency_id' => $contract->currency_id,
                    'currency' => $contract->currency,
                    'payment_frequency' => $contract->payment_frequency,
                ] : null,
            ];
        });

        // 2. Get Payslips for a selected month/year, default to current
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);

        $payslips = Payslip::with(['member', 'items', 'currency'])
            ->where('tenant_id', $tenant->id)
            ->where('month', $month)
            ->where('year', $year)
            ->latest()
            ->get();

        $paymentMethods = PaymentMethod::where('tenant_id', $tenant->id)->get();

        return Inertia::render('ERP/Payroll/Index', [
            'members' => $membersWithContracts,
            'payslips' => $payslips,
            'paymentMethods' => $paymentMethods,
            'filters' => [
                'month' => $month,
                'year' => $year,
            ]
        ]);
    }

    public function updateContract(Request $request)
    {
        $this->checkAddon();
        $tenant = $this->resolveTenant();

        $validated = $request->validate([
            'member_id' => 'required|exists:erp_team_members,id',
            'base_salary' => 'required|numeric|min:0',
        ]);

        $member = TeamMember::where('tenant_id', $tenant->id)->findOrFail($validated['member_id']);

        PayrollContract::updateOrCreate(
            ['tenant_id' => $tenant->id, 'member_id' => $member->id],
            [
                'base_salary' => $validated['base_salary'],
                'currency_id' => $tenant->base_currency_id, // Default to business currency
                'payment_frequency' => 'monthly',
                'is_active' => true,
            ]
        );

        return back()->with('success', __('erp.payroll_contract_updated'));
    }

    public function generate(Request $request)
    {
        $this->checkAddon();
        $tenant = $this->resolveTenant();

        $validated = $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2000|max:2100',
        ]);

        $month = $validated['month'];
        $year = $validated['year'];

        DB::transaction(function () use ($tenant, $month, $year) {
            $contracts = PayrollContract::where('tenant_id', $tenant->id)
                ->where('is_active', true)
                ->get();

            foreach ($contracts as $contract) {
                // Check if payslip already exists
                $exists = Payslip::where('tenant_id', $tenant->id)
                    ->where('member_id', $contract->member_id)
                    ->where('month', $month)
                    ->where('year', $year)
                    ->exists();

                if (!$exists) {
                    Payslip::create([
                        'tenant_id' => $tenant->id,
                        'member_id' => $contract->member_id,
                        'currency_id' => $contract->currency_id,
                        'month' => $month,
                        'year' => $year,
                        'worked_days' => 30, // Default assumption
                        'absent_days' => 0,
                        'base_amount' => $contract->base_salary,
                        'net_amount' => $contract->base_salary,
                        'status' => 'draft',
                    ]);
                }
            }
        });

        return back()->with('success', __('erp.payroll_generated'));
    }

    public function updatePayslipItems(Request $request, $id)
    {
        $this->checkAddon();
        $tenant = $this->resolveTenant();

        $payslip = Payslip::where('tenant_id', $tenant->id)->where('status', 'draft')->findOrFail($id);

        $validated = $request->validate([
            'worked_days' => 'nullable|integer|min:0',
            'absent_days' => 'nullable|integer|min:0',
            'items' => 'array',
            'items.*.type' => 'required|in:bonus,deduction',
            'items.*.amount' => 'required|numeric|min:0.01',
            'items.*.description' => 'required|string|max:255',
        ]);

        DB::transaction(function () use ($payslip, $validated) {
            if (isset($validated['worked_days'])) {
                $payslip->worked_days = $validated['worked_days'];
            }
            if (isset($validated['absent_days'])) {
                $payslip->absent_days = $validated['absent_days'];
            }

            $payslip->items()->delete(); // Clear old items
            
            $totalBonus = 0;
            $totalDeduction = 0;

            if (!empty($validated['items'])) {
                foreach ($validated['items'] as $item) {
                    $payslip->items()->create($item);
                    if ($item['type'] === 'bonus') {
                        $totalBonus += $item['amount'];
                    } else {
                        $totalDeduction += $item['amount'];
                    }
                }
            }

            $payslip->net_amount = $payslip->base_amount + $totalBonus - $totalDeduction;
            $payslip->save();
        });

        return back()->with('success', __('erp.payslip_updated'));
    }

    public function markAsPaid(Request $request, $id)
    {
        $user = $this->checkAddon();
        $tenant = $this->resolveTenant();

        $validated = $request->validate([
            'payment_method_id' => 'required|exists:erp_payment_methods,id',
        ]);

        $payslip = Payslip::with('member')->where('tenant_id', $tenant->id)->findOrFail($id);

        if ($payslip->status === 'paid') {
            return back()->with('error', __('erp.payslip_already_paid'));
        }

        DB::transaction(function () use ($tenant, $payslip, $user, $validated) {
            $payslip->status = 'paid';
            $payslip->paid_at = now();
            $payslip->payment_method_id = $validated['payment_method_id'];
            $payslip->save();

            Expense::create([
                'tenant_id' => $tenant->id,
                'title' => __('erp.payroll_expense_title', ['name' => $payslip->member->name, 'month' => $payslip->month, 'year' => $payslip->year]),
                'amount' => $payslip->net_amount,
                'currency_id' => $payslip->currency_id,
                'category' => 'Payroll',
                'date' => now()->toDateString(),
                'description' => __('erp.salary_payment_for', ['name' => $payslip->member->name, 'month' => $payslip->month, 'year' => $payslip->year]),
                'created_by' => $user->id,
            ]);
        });

        return back()->with('success', __('erp.payslip_marked_paid'));
    }
}
