<?php

namespace Modules\ERP\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Modules\ERP\Models\Tenant;
use Modules\ERP\Models\Invoice;

class StoreInvoiceRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'client_id' => 'required|exists:erp_tenant_clients,id',
            'project_id' => 'nullable|exists:erp_projects,id',
            'invoice_number' => 'required|string',
            'issued_at' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issued_at',
            'amount_currency' => 'nullable|string|size:3',
            'items' => 'required|array|min:1',
            'items.*.type' => 'required|in:simple,quantity,timer',
            'items.*.title' => 'required|string',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.quantity' => 'required|numeric|min:0',
            'items.*.product_id' => 'nullable|exists:erp_products,id',
            'items.*.uom' => 'nullable|string',
            'costs' => 'nullable|array',
            'discount_amount' => 'nullable|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $tenant = Tenant::where('user_id', Auth::id())->first();
            if (!$tenant && Auth::guard('erp_team')->check()) {
                $tenant = Auth::guard('erp_team')->user()->tenant;
            }

            if ($tenant && $this->invoice_number) {
                $exists = Invoice::where('tenant_id', $tenant->id)
                    ->where('invoice_number', $this->invoice_number)
                    ->exists();

                if ($exists) {
                    $validator->errors()->add('invoice_number', 'The invoice number has already been taken for this tenant.');
                }
            }
        });
    }
}
