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
            'duration_preset' => ['required', 'string', 'in:1_month,3_months,6_months,1_year,lifetime,package,custom'],
            'package_id' => ['nullable', 'integer', 'exists:serial_software_packages,id'],
            'custom_expires_at' => ['required_if:duration_preset,custom', 'nullable', 'date'],
        ];
    }
}
