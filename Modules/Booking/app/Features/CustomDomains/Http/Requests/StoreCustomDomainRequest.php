<?php

namespace Modules\Booking\app\Features\CustomDomains\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomDomainRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            // Regex to allow basic domain name structures (e.g. book.clinic.com)
            'domain' => ['required', 'string', 'max:255', 'regex:/^(?!:\/\/)(?=.{1,255}$)((.{1,63}\.){1,127}(?![0-9]*$)[a-z0-9-]+\.?)$/i', 'unique:booking_custom_domains,domain'],
        ];
    }
}
