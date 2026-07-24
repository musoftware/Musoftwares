<?php

namespace App\Http\Requests\Admin\Points;

use Illuminate\Foundation\Http\FormRequest;

class AdjustUserPointsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'not_in:0'],
            'reason' => ['required', 'string', 'max:255'],
        ];
    }
}
