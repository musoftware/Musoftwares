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

    /** @param list<UploadedFile> $files */
    public function attach(BuilderMessage $message, array $files): void
    {
        $folder = $message->project->storageFolder().'/uploads';

        foreach ($files as $file) {
            $message->attachments()->create([
                'project_id' => $message->project_id,
                'original_name' => mb_substr($file->getClientOriginalName(), 0, 200),
                // the type comes from the file's content, not from what the browser claims
                'mime' => (string) $file->getMimeType(),
                'size' => (int) $file->getSize(),
                'path' => $file->store($folder, self::DISK),
            ]);
        }
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

        $blocks = $message->attachments->map(fn (BuilderAttachment $file) => $this->block($file))->filter()->values()->all();
        $text = trim($message->content);
        $blocks[] = ['type' => 'text', 'text' => $text !== '' ? $text : self::FILES_ONLY];

        return $blocks;
    }

    public function contents(BuilderAttachment $attachment): ?string
    {
        return Storage::disk(self::DISK)->get($attachment->path);
    }

    /** @return array<string, mixed>|null */
    private function block(BuilderAttachment $file): ?array
    {
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
