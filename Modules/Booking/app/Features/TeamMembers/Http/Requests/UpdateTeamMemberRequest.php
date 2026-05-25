<?php

namespace Modules\Booking\app\Features\TeamMembers\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTeamMemberRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'job_title' => ['sometimes', 'string', 'max:255'],
            'bio' => ['sometimes', 'string', 'max:1000'],
            'is_bookable' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }
}
