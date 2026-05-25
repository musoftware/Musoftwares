<?php

namespace App\Http\Requests\Admin\PriceCalculator;

use Illuminate\Foundation\Http\FormRequest;

class StoreProposalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'project_details' => 'required',
            'parsed_data'     => 'required|array',
            'total_cost_egp'  => 'required|numeric',
            'ascii_table'     => 'required'
        ];
    }
}
