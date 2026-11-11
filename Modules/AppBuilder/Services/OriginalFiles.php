<?php

namespace Modules\AppBuilder\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Modules\AppBuilder\Models\BuilderAttachment;
use Modules\AppBuilder\Models\BuilderMessage;
use Modules\AppBuilder\Models\BuilderProject;

/**
 * Big design pictures reach the chat as smaller previews made in the browser, so the chat stays fast. Once the
 * customer pays, the build waits a while for the full-quality originals; it starts anyway when they send them,
 * when they choose to go on with what they sent, or when the wait is over.
 */
class OriginalFiles
{
    public function __construct(private readonly AttachmentStore $attachments) {}

    /** When the paid build may start: now, or after a wait for the originals of the previews. */
    public function dueAt(BuilderProject $project): ?\Illuminate\Support\Carbon
    {
        return $this->previews($project)->isEmpty() ? null : now()->addMinutes((int) config('builder.limits.originals_wait_minutes'));
    }

    public function isWaiting(BuilderProject $project): bool
    {
        return $project->originals_due_at !== null && $project->originals_due_at->isFuture();
    }

    /** @return Collection<int, BuilderAttachment> */
    public function previews(BuilderProject $project): Collection
    {
        return $project->attachments()->where('quality', BuilderAttachment::PREVIEW)->get();
    }

    /** @param  list<UploadedFile>  $files */
    public function store(BuilderProject $project, array $files): void
    {
        $note = $project->messages()->create([
            'role' => BuilderMessage::USER,
            'content' => __('builder.originals.sent', [], $project->customerLanguage()),
        ]);
        $this->attachments->attach($note, $files, [], BuilderAttachment::ORIGINAL);
        $this->stopWaiting($project);
    }

    public function stopWaiting(BuilderProject $project): void
    {
        $project->update(['originals_due_at' => null]);
    }

    /**
     * The files the build gets: once originals came, the smaller previews are left out.
     *
     * @return Collection<int, BuilderAttachment>
     */
    public function forBuild(BuilderProject $project): Collection
    {
        $files = $project->attachments()->get();
        $hasOriginals = $files->contains(fn (BuilderAttachment $file) => $file->quality === BuilderAttachment::ORIGINAL);

        return $hasOriginals ? $files->reject(fn (BuilderAttachment $file) => $file->quality === BuilderAttachment::PREVIEW)->values() : $files;
    }
}
