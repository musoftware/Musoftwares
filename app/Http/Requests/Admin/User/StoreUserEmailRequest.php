<?php

namespace App\Http\Requests\Admin\User;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        return $user !== null && $user->isAdmin();
    }

    public function rules(): array
    {
        $userParam = $this->route('user');
        $userId    = is_object($userParam) && method_exists($userParam, 'getKey')
            ? (int) $userParam->getKey()
            : (int) ($userParam ?? 0);

        return [
            'email'       => [
                'required',
                'string',
                'email:rfc',
                'max:191',
                Rule::unique('user_emails', 'email'),
                function (string $attribute, mixed $value, \Closure $fail) use ($userId): void {
                    $owner = User::findForLogin((string) $value);
                    if ($owner && (int) $owner->id !== $userId) {
                        $fail('This email already belongs to another user.');
                    }
                },
            ],
            'verified_at' => ['nullable', 'boolean'],
        ];
    }
}
