<?php

namespace Modules\AppBuilder\Services;

use Illuminate\Http\UploadedFile;
use Modules\AppBuilder\Models\BuilderMessage;
use Modules\AppBuilder\Models\BuilderProject;

/**
 * How far a paid build is, as the customer sees it: a progress bar with one plain line on the last finished
 * part, and a screenshot in the chat whenever there is something new to look at.
 */
class BuildSnapshots
{
    /** The bar never shows 100 before the program is really delivered. */
    private const MAX_PERCENT = 99;

    public function __construct(private readonly AttachmentStore $attachments, private readonly BuilderPush $push) {}

    /** Moves the bar (never backwards) and keeps the screenshot. False when the build already sent its share of pictures. */
    public function update(BuilderProject $project, int $percent, string $note, ?UploadedFile $image = null): bool
    {
        $project->update([
            'progress_percent' => min(self::MAX_PERCENT, max((int) $project->progress_percent, $percent)),
            'progress_note' => mb_substr(trim($note), 0, 300),
        ]);

        if ($image === null) {
            return true;
        }
        if ($this->sentThisBuild($project) >= (int) config('builder.limits.snapshots_per_build')) {
            return false;
        }

        $message = $project->messages()->create(['role' => BuilderMessage::PROGRESS, 'content' => trim($note)]);
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
