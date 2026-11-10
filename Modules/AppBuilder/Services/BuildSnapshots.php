<?php

namespace Modules\AppBuilder\Services;

use Illuminate\Http\UploadedFile;
use Modules\AppBuilder\Models\BuilderMessage;
use Modules\AppBuilder\Models\BuilderProject;

/**
 * A screenshot the build sends when a big part of the program works, so the customer sees the work
 * grow in the chat instead of only a progress line. A few per build, never a flood.
 */
class BuildSnapshots
{
    public function __construct(private readonly AttachmentStore $attachments, private readonly BuilderPush $push) {}

    /** False when this build already sent as many as it may. */
    public function add(BuilderProject $project, UploadedFile $image, string $caption): bool
    {
        if ($this->sentThisBuild($project) >= (int) config('builder.limits.snapshots_per_build')) {
            return false;
        }

        $message = $project->messages()->create(['role' => BuilderMessage::PROGRESS, 'content' => trim($caption)]);
        $this->attachments->attach($message, [$image]);
        $this->push->notify($project, 'snapshot');

        return true;
    }

    private function sentThisBuild(BuilderProject $project): int
    {
        return $project->messages()
            ->where('role', BuilderMessage::PROGRESS)
            ->when($project->paid_at, fn ($query) => $query->where('created_at', '>=', $project->paid_at))
            ->count();
    }
}
