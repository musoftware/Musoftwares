<?php

namespace Modules\WhatsappSender\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class SendWhatsappMessageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'recipient_phone' => [
                'required',
                'string',
                'regex:/^\+?[1-9]\d{6,14}$/',
            ],
            'message_body' => [
                'required',
                'string',
                'min:1',
                'max:4096',
            ],
            'whatsapp_account_id' => [
                'nullable',
                'integer',
                'exists:whatsapp_accounts,id',
            ],
            'message_type' => [
                'nullable',
                'string',
                'in:text,template',
            ],
            'template_name' => [
                'required_if:message_type,template',
                'nullable',
                'string',
                'max:255',
            ],
            'template_language' => [
                'nullable',
                'string',
                'max:10',
            ],
            'template_components' => [
                'nullable',
                'array',
            ],
        ];
    }

    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        return [
            'recipient_phone.required' => 'The recipient phone number is required.',
            'recipient_phone.regex' => 'The recipient phone number must be a valid E.164 format number (e.g., 201001234567 or +201001234567).',
            'message_body.required' => 'The message body content cannot be empty.',
            'message_body.max' => 'The message body cannot exceed 4,096 characters.',
            'whatsapp_account_id.exists' => 'The specified WhatsApp account does not exist or has been deleted.',
            'template_name.required_if' => 'The template name is required when message_type is set to template.',
        ];
    }

    /**
     * Handle failed validation for API request JSON envelope.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Validation failed for WhatsApp API request.',
                'errors' => $validator->errors(),
                'timestamp' => now()->toIso8601String(),
            ], 422)
        );
    }
}
