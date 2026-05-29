<?php

namespace Modules\ERP\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaskRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'task_name'       => 'sometimes|required|string|max:255',
            'title'           => 'sometimes|required|string|max:255',
            'task_description'=> 'nullable|string',
            'client_id'       => 'nullable|exists:erp_tenant_clients,id',
            'project_id'      => 'nullable|exists:erp_projects,id',
            'priority'        => 'nullable|in:low,normal,high,urgent',
            'status'          => 'nullable|in:open,in_progress,review,completed,archived',
            'due_date'        => 'nullable|date',
        ];
    }
}
