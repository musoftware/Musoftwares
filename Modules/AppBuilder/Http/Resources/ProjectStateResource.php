<?php

namespace Modules\AppBuilder\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\AppBuilder\Models\BuilderMessage;
use Modules\AppBuilder\Models\BuilderProject;
use Modules\AppBuilder\Services\DeliveryStore;
use Modules\AppBuilder\Services\LiveTrials;

/**
 * Everything the chat page draws: the lines, the open quote, the build progress and the delivered program.
 * The spec, the customer's contact data and the costs never leave the server.
 *
 * @mixin BuilderProject
 */
class ProjectStateResource extends JsonResource
{
    public static $wrap = null;

    private const ANSWER_MINUTES = 3;

    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'status' => $this->status,
            'open_for_chat' => $this->isOpenForChat(),
            'messages' => $this->messages->map(fn (BuilderMessage $message) => $this->line($message))->values(),
            'quote' => $this->status === BuilderProject::QUOTED ? $this->quote() : null,
            'progress' => in_array($this->status, [BuilderProject::QUEUED, BuilderProject::BUILDING, BuilderProject::DESIGNING], true) && ! $this->awaiting_answer ? $this->progressLine() : null,
            // the bar shows once the build has said how far it is
            'progress_percent' => $this->status === BuilderProject::BUILDING && ! $this->awaiting_answer ? $this->progress_percent : null,
            'waiting_for_answer' => $this->isWaitingForAnswer(),
            // what the customer waits for: a link being opened or a file being read
            'looking' => $this->awaiting_look ? (app(\Modules\AppBuilder\Services\LinkLooks::class)->isReadingAFile($this->resource) ? 'file' : 'link') : null,
            // the look is done and the answer is being written in another request, so the page keeps asking
            'answering' => ! $this->awaiting_look && $this->isAnsweringAfterLook(),
            'trial' => $this->hasDelivery() ? $this->trial() : null,
            // asked from the first message: replies, a discount, a problem and the delivery all reach the customer
            'push' => \Modules\AppBuilder\Services\BuilderPush::enabled() && ! $this->push_token ? route('builder.push', $this->resource) : null,
            'balance' => $this->hasBalanceDue() ? [
                'due' => number_format($this->balanceDue(), 2),
                'currency' => $this->currency,
                'pay_url' => route('builder.pay', $this->resource),
            ] : null,
            'delivery' => $this->hasDelivery() ? $this->delivery() : null,
            'failed' => $this->status === BuilderProject::FAILED,
        ];
    }

    private function line(BuilderMessage $message): array
    {
        return [
            'id' => $message->id,
            'role' => $message->role,
            'text' => $message->content,
            'delivered_version' => $message->payload['delivered_version'] ?? null,
            'ideas' => $message->payload['ideas'] ?? [],
            'files' => $this->fileNames($message),
            'images' => $this->images($message),
        ];
    }

    /** A customer's own files are shown by name; pictures the build or a shared link made are shown as pictures. */
    private function fileNames(BuilderMessage $message): array
    {
        if ($message->role === BuilderMessage::PROGRESS) {
            return [];
        }
        $looks = collect($message->payload['looks'] ?? []);
        $madeHere = $looks->pluck('full_id')->merge($looks->pluck('piece_ids')->flatten())->filter();

        return $message->attachments->reject(fn ($file) => $madeHere->contains($file->id))->pluck('original_name')->values()->all();
    }

    /** @return list<string> */
    private function images(BuilderMessage $message): array
    {
        $shown = $message->role === BuilderMessage::PROGRESS
            ? $message->attachments->filter->isImage()->pluck('id')
            : collect($message->payload['looks'] ?? [])->pluck('full_id')->filter();

        return $shown->map(fn ($id) => route('builder.file', [$this->resource, $id]))->values()->all();
    }

    private function quote(): array
    {
        return [
            'title' => $this->title,
            'amount' => number_format((float) $this->amount, 2),
            'currency' => $this->currency,
            'free' => (bool) $this->free_fix,
            'design_fee' => (float) $this->design_fee > 0 ? number_format((float) $this->design_fee, 2) : null,
            'shares' => array_values((array) config('builder.deposit_shares')),
            'study' => $this->study_status ? [
                'status' => $this->study_status,
                'url' => $this->study_status === \Modules\AppBuilder\Services\Study\FeasibilityStudy::READY ? route('builder.study', $this->resource) : null,
                'fee' => (float) $this->study_fee > 0 ? number_format((float) $this->study_fee, 2) : null,
            ] : null,
            // offered once if the customer starts to leave; the page asks before it takes it
            'leave_offer' => app(\Modules\AppBuilder\Services\LeaveDiscount::class)->offerFor($this->resource),
            'discount_url' => route('builder.discount', $this->resource),
            'left_url' => route('builder.left', $this->resource),
            // the customer came back from the discount notification: show the offer at once
            'offer_now' => $this->leave_reminder_at !== null,
            'discount' => $this->leave_discount_at && (float) $this->leave_discount_usd > 0 && ! $this->free_fix ? [
                'percent' => $this->leave_discount_percent,
                'before' => number_format((float) $this->amount + (float) $this->leave_discount_usd, 2),
            ] : null,
            'pay_url' => route('builder.pay', $this->resource),
            'fix_url' => route('builder.fix', $this->resource),
        ];
    }

    private function progressLine(): string
    {
        $step = match ($this->status) {
            BuilderProject::QUEUED => 'queued',
            BuilderProject::DESIGNING => 'designing',
            default => $this->progress_step ?: 'started',
        };
        // the engineer's own plain line on the last finished part reads better than a step and a file name
        if ($this->status === BuilderProject::BUILDING && $this->progress_note) {
            return $this->progress_note;
        }

        return __('builder.steps.'.$step);
    }

    private function delivery(): array
    {
        return [
            'version' => $this->version,
            // a server program (Laravel, WordPress) has no page to open here, only files and SETUP.md
            'preview_url' => $this->previewUrl(),
            'download_url' => $this->hasBalanceDue() ? null : route('builder.download', $this->resource),
            'apk_url' => $this->apkUrl(),
        ];
    }

    /** The live trial: the link and its password only while it is live, and only on this customer's page. */
    private function trial(): array
    {
        $trials = app(LiveTrials::class);
        $live = $trials->isLive($this->resource);

        return [
            'can_request' => $trials->canRequest($this->resource),
            'waiting' => $trials->isWaiting($this->resource),
            'failed' => $this->demo_status === LiveTrials::FAILED,
            'url' => $live ? $this->demo_url : null,
            'password' => $live ? $this->demo_password : null,
            'until' => $live ? $this->demo_expires_at?->timezone('Africa/Cairo')->format('H:i') : null,
            'request_url' => route('builder.trial', $this->resource),
        ];
    }

    /** The Android app to install, once it is fully paid. */
    private function apkUrl(): ?string
    {
        if ($this->hasBalanceDue() || app(DeliveryStore::class)->file($this->resource, DeliveryStore::APK_FILE) === null) {
            return null;
        }

        return route('builder.preview', ['project' => $this->resource, 'path' => DeliveryStore::APK_FILE]);
    }

    private function previewUrl(): ?string
    {
        $store = app(DeliveryStore::class);
        $entry = collect([DeliveryStore::ENTRY_FILE, DeliveryStore::BUILT_ENTRY_FILE, DeliveryStore::APP_PREVIEW_FILE])->first(fn (string $path) => $store->file($this->resource, $path) !== null);

        return $entry ? route('builder.preview', ['project' => $this->resource, 'path' => $entry]) : null;
    }

    /** The last line is the customer's, sent with a link or a file, and its answer is still on its way (a few minutes at most). */
    private function isAnsweringAfterLook(): bool
    {
        $last = $this->messages->last();

        return $last instanceof BuilderMessage
            && $last->role === BuilderMessage::USER
            && ($last->payload['looks'] ?? []) !== []
            && $last->created_at?->gt(now()->subMinutes(self::ANSWER_MINUTES)) === true;
    }
}
