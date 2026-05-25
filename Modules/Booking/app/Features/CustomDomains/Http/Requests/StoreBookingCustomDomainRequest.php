<?php

namespace Modules\Booking\app\Features\CustomDomains\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingCustomDomainRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Policies should handle actual authorization
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'domain' => [
                'required',
                'string',
                'max:255',
                'unique:booking_custom_domains,domain',
                // Add more complex regex to validate domain format
                'regex:/^(?!:\/\/)(?=.{1,255}$)((.{1,63}\.){1,127}(?![0-9]*$)[a-z0-9-]+\.?)$/i'
            ]
        ];
    }

    public function messages(): array
    {
        return [
            'domain.regex' => 'The domain format is invalid. Please enter a valid domain (e.g. book.yourclinic.com).'
        ];
    }
}
