<?php

namespace Modules\AppBuilder\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Modules\AppBuilder\Models\BuilderAttachment;

/** A chat line: text, files (images or PDFs), or both. */
class SendMessageRequest extends FormRequest
{
    public function rules(): array
    {
        $limits = config('builder.limits');
        $types = implode(',', [...BuilderAttachment::IMAGE_TYPES, BuilderAttachment::PDF]);

        return [
            'text' => ['nullable', 'required_without:files', 'string', 'max:'.(int) $limits['message_chars']],
            'files' => ['nullable', 'array', 'max:'.(int) $limits['files_per_message']],
            // mimetypes reads the file's content, so a renamed file of another kind is refused
            'files.*' => ['file', 'max:'.(int) $limits['file_kb'], 'mimetypes:'.$types],
        ];
    }

    public function text(): string
    {
        return trim((string) $this->validated('text'));
    }

    /** @return list<UploadedFile> */
    public function uploads(): array
    {
        return array_values($this->file('files', []));
    }
}
