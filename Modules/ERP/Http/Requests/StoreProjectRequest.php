<?php

namespace Modules\ERP\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'client_id' => 'required|exists:erp_tenant_clients,id',
            'status' => 'required|string|in:Planning,Active,On Hold,Completed,Cancelled',
            'budget' => 'nullable|numeric|min:0',
            'due_date' => 'nullable|date',
        ];
    }
}
