<?php

namespace Modules\AppBuilder\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** A customer brings their own project: a Google Drive link to it, and what they need changed. */
class BringProjectRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'drive_url' => ['required', 'url', 'max:2000', 'regex:#^https://(drive|docs)\.google\.com/#'],
            'need' => ['required', 'string', 'min:5', 'max:'.(int) config('builder.limits.message_chars')],
        ];
    }

    public function messages(): array
    {
        return ['drive_url.regex' => __('builder.inspection.drive_only')];
    }
}
