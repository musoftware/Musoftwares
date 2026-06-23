<?php

namespace App\Http\Requests\Admin\User;

use Illuminate\Foundation\Http\FormRequest;

class UploadUserFileRequest extends FormRequest
{
    private const ALLOWED_EXTENSIONS = [
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg',
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'rtf',
        'zip', 'rar', '7z', 'tar',
        'mp4', 'mov', 'avi', 'mkv', 'mp3', 'wav', 'ogg',
        'json', 'xml', 'sql',
    ];

    public function authorize(): bool
    {
        return $this->user() && $this->user()->hasAnyRole(['admin', 'super_admin', 'Admin', 'superadmin']);
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required', 'file', 'max:51200',
                function ($attr, $value, $fail) {
                    $ext = strtolower($value->getClientOriginalExtension());
                    if (!in_array($ext, self::ALLOWED_EXTENSIONS)) {
                        $fail("File type .{$ext} is not allowed.");
                    }
                },
            ],
        ];
    }
}
