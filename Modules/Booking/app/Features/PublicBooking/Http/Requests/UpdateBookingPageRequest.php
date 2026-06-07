<?php

namespace Modules\Booking\app\Features\PublicBooking\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBookingPageRequest extends FormRequest
{
    public function authorize()
    {
        return true; // We handle authorization via middleware/policies
    }

    public function rules()
    {
        $tenantId = (app()->bound('currentTenant') ? app('currentTenant')->id : auth()->id());
        // Find existing setting to ignore its ID for unique slug validation
        $setting = \Modules\Booking\app\Features\PublicBooking\Models\BookingPageSetting::withoutGlobalScope('tenant')
                        ->where('tenant_id', $tenantId)
                        ->first();
                        
        $ignoreId = $setting ? $setting->id : null;

        return [
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', 'unique:booking_page_settings,slug,' . $ignoreId],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'primary_color' => ['nullable', 'string', 'regex:/^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/'],
            'logo_path' => ['nullable', 'string'], // In practice, handle file uploads separately
            'is_active' => ['boolean'],
        ];
    }
}
