<?php

namespace Modules\AppBuilder\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** The owner's corrections to a request before the engineer gets it, or the reason it is turned down. */
class OwnerReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    public function rules(): array
    {
        return [
            'spec' => ['nullable', 'string', 'max:20000'],
            'customer_words' => ['nullable', 'string', 'max:20000'],
            'amount' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /** @return array{spec?: string, customer_words?: string, amount?: float|null} */
    public function changes(): array
    {
        return array_filter([
            'spec' => $this->validated('spec'),
            'customer_words' => $this->validated('customer_words'),
            'amount' => $this->filled('amount') ? (float) $this->validated('amount') : null,
        ], fn ($value) => $value !== null);
    }
}
