<?php

namespace App\Http\Requests\Admin\Invoice;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
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
            'deleted_items'      => 'nullable|array',
            'deleted_items.*'    => 'exists:invoice_items,id'
        ];
    }
}
