<?php

namespace App\Http\Requests\Admin\User;

class UpdateUserNoteRequest extends StoreUserNoteRequest
{
    public function rules(): array
    {
        return [
            'title'      => 'sometimes|required|string|max:255',
            'content'    => 'sometimes|required|string',
            'category'   => 'sometimes|required|in:password,anydesk,notes',
            'expires_at' => 'nullable|date',
        ];
    }
}
