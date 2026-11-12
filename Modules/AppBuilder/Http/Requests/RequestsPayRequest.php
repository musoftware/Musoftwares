<?php

namespace Modules\AppBuilder\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\AppBuilder\Services\ProjectRequests;

/** The customer's details and the requests they ticked in the list ("ids": "3,5,8"). */
class RequestsPayRequest extends FormRequest
{
    public function rules(): array
    {
        return (new StudyPayRequest)->rules() + ['ids' => ['required', 'string', 'max:200', 'regex:/^\d+(,\d+)*$/']];
    }

    /** @return list<int> */
    public function ids(): array
    {
        return array_slice(array_values(array_unique(array_map('intval', explode(',', (string) $this->validated('ids'))))), 0, ProjectRequests::MAX_PER_PAYMENT);
    }

    /** @return array{client_name: string, client_email: string, client_whatsapp: string} */
    public function client(): array
    {
        return array_diff_key($this->validated(), ['ids' => true]);
    }
}
