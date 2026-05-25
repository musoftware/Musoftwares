<?php

namespace Modules\Booking\app\Features\MultiBranch\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBookingBranchRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'phone' => ['nullable', 'string', 'max:50'],
            'is_main_branch' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }
}
