<?php

namespace Modules\Booking\app\Features\Reminders\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWaTemplateRequest extends FormRequest
{
    public function authorize()
    {
        return true; // We handle authorization in policies or middleware
    }

    public function rules()
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'trigger_type' => ['required', 'string', Rule::in([
                'on_booking_confirmed',
                'before_1_hour',
                'before_2_hours',
                'before_24_hours',
                'after_completed'
            ])],
            'body' => ['required', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ];
    }
}
