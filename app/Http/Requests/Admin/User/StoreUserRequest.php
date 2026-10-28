<?php

namespace App\Http\Requests\Admin\User;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    use ValidatesUserFields;

    public function authorize(): bool
    {
        return $this->user() && $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return ['role' => 'required|in:admin,client,user,employee,manager,moderator,software_reseller'] + $this->userFieldRules();
    }

    public function withValidator($validator): void
    {
        $this->requireLastName($validator);
    }
}
