<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class PalindromeCheckerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Determine if validation should run.
     */
    public function rules(): array
    {
        // Only validate on POST requests
        if ($this->isMethod('post')) {
            return [
                'text' => [
                    'required',
                    'string',
                    'min:1',
                    'max:10000',
                    function ($attribute, $value, $fail) {
                        // Check for potentially malicious content
                        if (preg_match('/<script|javascript:|vbscript:|onload=|onerror=/i', $value)) {
                            $fail('The text contains potentially unsafe content.');
                        }

                        // Check for excessive repetition (potential spam)
                        if (strlen($value) > 100 && $this->hasExcessiveRepetition($value)) {
                            $fail('The text contains excessive repetition which may indicate spam.');
                        }
                    },
                ],
                'preserve_case' => 'boolean',
                'include_spaces' => 'boolean',
            ];
        }

        // Return empty rules for GET requests
        return [];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'text.required' => 'Please enter some text to check for palindrome.',
            'text.string' => 'The input must be text.',
            'text.min' => 'The text must contain at least 1 character.',
            'text.max' => 'The text cannot exceed 10,000 characters.',
        ];
    }

    /**
     * Get custom attribute names for validator errors.
     */
    public function attributes(): array
    {
        return [
            'text' => 'text input',
            'preserve_case' => 'case preservation option',
            'include_spaces' => 'space inclusion option',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Trim whitespace from text input
        if ($this->has('text')) {
            $this->merge([
                'text' => trim($this->text),
            ]);
        }
    }

    /**
     * Check if text has excessive repetition (potential spam detection).
     */
    private function hasExcessiveRepetition(string $text): bool
    {
        // Check for repeated characters (more than 50% of text length)
        $charCount = strlen($text);
        $uniqueChars = count(array_unique(str_split($text)));

        if ($uniqueChars < 3 && $charCount > 50) {
            return true;
        }

        // Check for repeated patterns
        $patterns = ['aaaa', 'bbbb', 'cccc', 'dddd', 'eeee', 'ffff'];
        foreach ($patterns as $pattern) {
            if (substr_count($text, $pattern) > 2) {
                return true;
            }
        }

        return false;
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        // Log validation failures for monitoring
        \Log::info('Palindrome checker validation failed', [
            'user_id' => auth()->id(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'errors' => $validator->errors()->toArray(),
        ]);

        parent::failedValidation($validator);
    }
}
