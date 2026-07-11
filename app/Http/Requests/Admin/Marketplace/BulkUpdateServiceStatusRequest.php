<?php

namespace App\Http\Requests\Admin\Marketplace;

use Illuminate\Foundation\Http\FormRequest;

class BulkUpdateServiceStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:marketplace_services,id',
            'action' => 'required|in:approve,decline,suspend',
        ];
    }
}
