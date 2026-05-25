<?php

namespace App\Http\Requests\Admin\PriceCalculator;

use Illuminate\Foundation\Http\FormRequest;

class CalculateAiProposalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'project_details' => 'required|string|min:20',
        ];
    }
}
