<?php

namespace Modules\ERP\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

use Illuminate\Support\Facades\Auth;
use Modules\ERP\Models\Invoice;

class UpdateInvoiceRequest extends FormRequest
{
    public function authorize()
    {
        $invoice = $this->route('invoice');
        if (!$invoice) return false;
        return $invoice->tenant_id === $this->user()->tenant->id ?? null;
    }

    public function rules()
    {
        return [
            'client_id' => 'required|exists:erp_tenant_clients,id',
            'project_id' => 'nullable|exists:erp_projects,id',
            'issued_at' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issued_at',
            'amount_currency' => 'nullable|string|size:3',
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable',
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
}
