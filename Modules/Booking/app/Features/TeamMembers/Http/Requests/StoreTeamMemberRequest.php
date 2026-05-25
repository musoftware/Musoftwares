<?php

namespace Modules\Booking\app\Features\TeamMembers\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTeamMemberRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Policies will handle authorization
    }

    public function rules()
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'], // Assuming user is created
            'job_title' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'is_bookable' => ['boolean'],
        ];
    }
}
