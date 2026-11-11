<?php

namespace Modules\AppBuilder\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\AppBuilder\Models\BuilderAttachment;
use Modules\AppBuilder\Models\BuilderMessage;

/** Keeps the customer's files with their message and turns them into blocks Claude can see. */
class AttachmentStore
{
    private const DISK = 'local';

    /** Said to Claude when the customer sent files with no text. */
    private const FILES_ONLY = 'The customer sent these files without a message.';

    /**
     * @param  list<UploadedFile>  $files
     * @return list<BuilderAttachment>
     */
    /**
     * @param  list<string>  $previewNames  pictures the browser made smaller (the page says which)
     * @param  string|null  $quality  BuilderAttachment::ORIGINAL for the full files uploaded after payment
     */
    public function attach(BuilderMessage $message, array $files, array $previewNames = [], ?string $quality = null): array
    {
        $folder = $message->project->storageFolder().'/uploads';

        return array_map(fn (UploadedFile $file) => $message->attachments()->create([
                'project_id' => $message->project_id,
                'original_name' => mb_substr($file->getClientOriginalName(), 0, 200),
                // the type comes from the file's content, not from what the browser claims
                'mime' => ChatFileTypes::of($file) ?? (string) $file->getMimeType(),
                'size' => (int) $file->getSize(),
                'quality' => $quality ?? (in_array($file->getClientOriginalName(), $previewNames, true) ? BuilderAttachment::PREVIEW : null),
                'path' => $file->store($folder, self::DISK),
            ]), $files);
    }

    /** A message that never got an answer goes away with its files. */
    public function forget(BuilderMessage $message): void
    {
        foreach ($message->attachments()->withTrashed()->get() as $attachment) {
            Storage::disk(self::DISK)->delete($attachment->path);
            $attachment->forceDelete();
        }
    }

    /** @return string|list<array<string, mixed>> plain text, or the files followed by the text */
    public function contentFor(BuilderMessage $message): string|array
    {
        // the chat model reads only the line under a build screenshot; the picture is for the customer
        if ($message->attachments->isEmpty() || $message->role === BuilderMessage::PROGRESS) {
            return $message->forModel();
        }

        // a shared link's full-page picture is for the customer; Claude reads the same page as sharp pieces
        $fullPages = collect($message->payload['looks'] ?? [])->pluck('full_id')->filter();
        // the full-quality originals are for the build only: too big for the chat model, which saw the previews
        $blocks = $message->attachments->reject(fn (BuilderAttachment $file) => $fullPages->contains($file->id) || $file->quality === BuilderAttachment::ORIGINAL)
            ->map(fn (BuilderAttachment $file) => $this->block($file))->filter()->values()->all();
        $text = trim($message->forModel());
        $text = $text !== '' ? $text : self::FILES_ONLY;

        // only spreadsheets or Word files: Claude reads what the worker read from them, as plain text
        return $blocks === [] ? $text : [...$blocks, ['type' => 'text', 'text' => $text]];
    }

    public function contents(BuilderAttachment $attachment): ?string
    {
        return Storage::disk(self::DISK)->get($attachment->path);
    }

    /** @return array<string, mixed>|null */
    /** Images and PDFs go to Claude as they are; spreadsheets and Word files reach it as the worker's reading. */
    private function block(BuilderAttachment $file): ?array
    {
        if (! $file->isImage() && $file->mime !== BuilderAttachment::PDF) {
            return null;
        }
        $data = $this->contents($file);
        if ($data === null) {
            return null;
        }

        $source = ['type' => 'base64', 'mediaType' => $file->mime, 'data' => base64_encode($data)];

        return $file->isImage()
            ? ['type' => 'image', 'source' => $source]
            : ['type' => 'document', 'source' => $source, 'title' => $file->original_name];
    }
}
