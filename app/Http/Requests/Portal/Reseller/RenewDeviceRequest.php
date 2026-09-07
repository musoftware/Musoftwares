<?php

namespace App\Http\Requests\Portal\Reseller;

use Illuminate\Foundation\Http\FormRequest;

class RenewDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && ($this->user()->isReseller() || $this->user()->isAdmin());
    }

    public function rules(): array
    {
        return [
            'duration_preset' => ['required', 'string', 'in:1_month,3_months,6_months,1_year,lifetime,custom'],
            'custom_expires_at' => ['required_if:duration_preset,custom', 'nullable', 'date'],
        ];
    }
}
