<?php

namespace App\Http\Requests\Admin\User;

class UpdateUserNoteRequest extends StoreUserNoteRequest
{
    public function rules(): array
    {
        return [
            'title'      => 'sometimes|required|string|max:255',
            'content'    => ['sometimes', 'required', 'string', 'max:50000', static function ($attr, $value, $fail) {
                if (!is_string($value) || !str_starts_with($value, self::CIPHER_PREFIX)) {
                    $fail('The content must be an encrypted cipher produced by SimpleCrypto (sc1: prefix required).');
                }
            }],
            'category'   => 'sometimes|required|in:password,anydesk,notes',
            'expires_at' => 'nullable|date',
        ];
    }
}
