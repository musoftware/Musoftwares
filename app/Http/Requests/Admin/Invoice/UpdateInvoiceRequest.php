<?php

namespace App\Http\Requests\Admin\Invoice;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->can('update', $this->route('invoice'));
    }

    public function rules(): array
    {
        return [
            'discount'           => 'nullable|numeric|min:0',
            'items'              => 'nullable|array',
            'items.*.id'         => 'nullable|exists:invoice_items,id',
            'items.*.item_title' => 'required|string|max:255',
            'items.*.amount'     => 'required|numeric|min:0',
            'items.*.qty'        => 'required|numeric|min:1',
            'items.*.item_type'  => 'required|in:quantity,simple,timer',
            'items.*.merged_from'=> 'nullable|array',
            'deleted_items'      => 'nullable|array',
            'deleted_items.*'    => 'exists:invoice_items,id',
            'cost_lines'         => 'nullable|array',
            'cost_lines.*.id'    => 'nullable|exists:invoice_cost_lines,id',
            'cost_lines.*.line_type' => 'required|in:direct,user_credit',
            'cost_lines.*.amount'    => 'required|numeric|min:0',
            'cost_lines.*.description' => 'nullable|string|max:500',
            'cost_lines.*.credit_user_id' => 'nullable|exists:users,id',
            'deleted_cost_lines' => 'nullable|array',
            'deleted_cost_lines.*' => 'exists:invoice_cost_lines,id'
        ];
    }
}
