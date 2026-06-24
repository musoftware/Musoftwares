<?php

namespace App\Http\Requests\Admin\SerialSoftware;

use App\Models\SerialSoftware;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSerialSoftwareStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(SerialSoftware::statuses())],
        ];
    }
}
