<?php

namespace Modules\AppBuilder\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Modules\AppBuilder\Services\ChatFileTypes;

/** A chat line: text, files (images, PDFs, Excel, Word or CSV), or both. */
class SendMessageRequest extends FormRequest
{
    public function rules(): array
    {
        $limits = config('builder.limits');

        return [
            'text' => ['nullable', 'required_without:files', 'string', 'max:'.(int) $limits['message_chars']],
            'files' => ['nullable', 'array', 'max:'.(int) $limits['files_per_message']],
            // the type is read from the file's content, so a renamed file of another kind is refused
            'files.*' => ['file', 'max:'.max((int) $limits['file_kb'], (int) $limits['office_file_kb']), function (string $attribute, mixed $file, \Closure $fail) use ($limits): void {
                $type = ChatFileTypes::of($file);
                if ($type === null) {
                    $fail(__('builder.file_type'));
                } elseif (! ChatFileTypes::isReadByWorker($type) && $file->getSize() > (int) $limits['file_kb'] * 1024) {
                    $fail(__('builder.file_too_big'));
                }
            }],
            'previews' => ['nullable', 'array', 'max:'.(int) $limits['files_per_message']],
            'previews.*' => ['string', 'max:255'],
        ];
    }

    /** @return list<string> the names of the pictures the page made smaller before sending */
    public function previewNames(): array
    {
        return array_values(array_map('strval', (array) $this->validated('previews', [])));
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
