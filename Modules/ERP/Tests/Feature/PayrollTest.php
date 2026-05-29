<?php

namespace Modules\ERP\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Modules\ERP\Models\Tenant;
use Modules\ERP\Models\TeamMember;
use Modules\ERP\Models\PayrollContract;
use Modules\ERP\Models\Payslip;
use App\Models\User;
use App\Models\Currency;

class PayrollTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $currency = Currency::factory()->create(['currency' => 'USD']);
        
        $this->user = User::factory()->create();
        $this->tenant = Tenant::factory()->create([
            'user_id' => $this->user->id,
            'base_currency_id' => $currency->id
        ]);
        
        \Modules\ERP\Models\UserSubscription::factory()->create([
            'user_id' => $this->user->id,
            'module_name' => 'erp-payroll',
            'status' => 'active'
        ]);
        
        $this->member = TeamMember::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email' => 'employee@example.com',
            'status' => 'active'
        ]);
    }

    public function test_can_update_payroll_contract()
    {
        $response = $this->actingAs($this->user)->post(route('erp.payroll.contract.update'), [
            'member_id' => $this->member->id,
            'base_salary' => 5000,
        ]);

        $response->assertRedirect();
        
        $this->assertDatabaseHas('erp_payroll_contracts', [
            'tenant_id' => $this->tenant->id,
            'member_id' => $this->member->id,
            'base_salary' => 5000,
        ]);
    }

    public function test_can_generate_draft_payslips()
    {
        // First, create a contract
        PayrollContract::create([
            'tenant_id' => $this->tenant->id,
            'member_id' => $this->member->id,
            'currency_id' => $this->tenant->base_currency_id,
            'base_salary' => 5000,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->post(route('erp.payroll.generate'), [
            'month' => 5,
            'year' => 2026,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('erp_payslips', [
            'tenant_id' => $this->tenant->id,
            'member_id' => $this->member->id,
            'month' => 5,
            'year' => 2026,
            'base_amount' => 5000,
            'net_amount' => 5000,
            'status' => 'draft',
        ]);
    }

    public function test_can_update_payslip_items_and_recalculate_net()
    {
        $payslip = Payslip::create([
            'tenant_id' => $this->tenant->id,
            'member_id' => $this->member->id,
            'currency_id' => $this->tenant->base_currency_id,
            'month' => 5,
            'year' => 2026,
            'base_amount' => 5000,
            'net_amount' => 5000,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($this->user)->post(route('erp.payroll.payslips.items.update', $payslip->id), [
            'items' => [
                [
                    'type' => 'bonus',
                    'amount' => 500,
                    'description' => 'Performance Bonus',
                ],
                [
                    'type' => 'deduction',
                    'amount' => 100,
                    'description' => 'Late Penalty',
                ]
            ]
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('erp_payslip_items', [
            'payslip_id' => $payslip->id,
            'type' => 'bonus',
            'amount' => 500,
        ]);

        $this->assertEquals(5400, $payslip->fresh()->net_amount);
    }

    public function test_can_mark_payslip_as_paid_and_generate_expense()
    {
        $payslip = Payslip::create([
            'tenant_id' => $this->tenant->id,
            'member_id' => $this->member->id,
            'currency_id' => $this->tenant->base_currency_id,
            'month' => 5,
            'year' => 2026,
            'worked_days' => 30,
            'absent_days' => 0,
            'base_amount' => 5000,
            'net_amount' => 5400,
            'status' => 'draft',
        ]);

        $paymentMethod = \Modules\ERP\Models\PaymentMethod::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->actingAs($this->user)->post(route('erp.payroll.payslips.mark_paid', $payslip->id), [
            'payment_method_id' => $paymentMethod->id
        ]);

        $response->assertRedirect();

        $this->assertEquals('paid', $payslip->fresh()->status);
        $this->assertEquals($paymentMethod->id, $payslip->fresh()->payment_method_id);

        // Verify Expense was generated
        $this->assertDatabaseHas('erp_expenses', [
            'tenant_id' => $this->tenant->id,
            'category' => 'Payroll',
            'amount' => 5400,
        ]);
    }
}
