<?php

namespace App\Http\Requests\Portal\Reseller;

use Illuminate\Foundation\Http\FormRequest;

class AssignDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && ($this->user()->isReseller() || $this->user()->isAdmin());
    }

    public function rules(): array
    {
        return [
            'serial_software_id' => ['required', 'integer', 'exists:serial_softwares,id'],
            'device_id' => ['required', 'string', 'max:255'],
            'customer_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'customer_name' => ['required_without:customer_user_id', 'nullable', 'string', 'max:255'],
            'customer_email' => ['required_without:customer_user_id', 'nullable', 'email', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:50'],
            'duration_preset' => ['required', 'string', 'in:1_day_trial,1_month,3_months,6_months,1_year,lifetime,package,custom'],
            'package_id' => ['nullable', 'integer', 'exists:serial_software_packages,id'],
            'custom_expires_at' => ['required_if:duration_preset,custom', 'nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
