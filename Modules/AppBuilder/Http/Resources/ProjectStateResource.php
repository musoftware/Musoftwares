<?php

namespace Modules\AppBuilder\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\AppBuilder\Models\BuilderMessage;
use Modules\AppBuilder\Models\BuilderProject;
use Modules\AppBuilder\Services\DeliveryStore;
use Modules\AppBuilder\Services\LiveTrials;
use Modules\SoftwareStore\Services\SoftwareOrderService;

/**
 * Everything the chat page draws: the lines, the open quote, the build progress and the delivered program.
 * The spec, the customer's contact data and the costs never leave the server.
 *
 * @mixin BuilderProject
 */
class ProjectStateResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'status' => $this->status,
            'open_for_chat' => $this->isOpenForChat(),
            'messages' => $this->messages->map(fn (BuilderMessage $message) => $this->line($message))->values(),
            'quote' => $this->status === BuilderProject::QUOTED ? $this->quote() : null,
            'progress' => in_array($this->status, [BuilderProject::QUEUED, BuilderProject::BUILDING, BuilderProject::DESIGNING], true) && ! $this->awaiting_answer ? $this->progressLine() : null,
            'waiting_for_answer' => $this->isWaitingForAnswer(),
            'trial' => $this->hasDelivery() ? $this->trial() : null,
            // offer notifications once there is something to wait for
            'push' => \Modules\AppBuilder\Services\BuilderPush::enabled() && ! $this->push_token && $this->paid_at !== null ? route('builder.push', $this->resource) : null,
            'balance' => $this->hasBalanceDue() ? [
                'due' => number_format($this->balanceDue(), 2),
                'currency' => $this->currency,
                'pay_url' => route('builder.pay', $this->resource),
            ] : null,
            'delivery' => $this->hasDelivery() ? $this->delivery() : null,
            'failed' => $this->status === BuilderProject::FAILED,
            'team_url' => $this->teamUrl(),
        ];
    }

    private function line(BuilderMessage $message): array
    {
        return [
            'id' => $message->id,
            'role' => $message->role,
            'text' => $message->content,
            'needs_team' => (bool) ($message->payload['needs_team'] ?? false),
            'delivered_version' => $message->payload['delivered_version'] ?? null,
            'ideas' => $message->payload['ideas'] ?? [],
            'files' => $message->attachments->pluck('original_name')->values(),
        ];
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
        $label = __('builder.steps.'.$step);

        return $this->progress_file ? $label.' — '.$this->progress_file : $label;
    }

    private function delivery(): array
    {
        return [
            'version' => $this->version,
            // a server program (Laravel, WordPress) has no page to open here, only files and SETUP.md
            'preview_url' => $this->previewUrl(),
            'download_url' => $this->hasBalanceDue() ? null : route('builder.download', $this->resource),
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

    private function previewUrl(): ?string
    {
        $store = app(DeliveryStore::class);
        $entry = collect([DeliveryStore::ENTRY_FILE, DeliveryStore::BUILT_ENTRY_FILE])->first(fn (string $path) => $store->file($this->resource, $path) !== null);

        return $entry ? route('builder.preview', ['project' => $this->resource, 'path' => $entry]) : null;
    }

    private function teamUrl(): string
    {
        $text = __('builder.team_message', ['link' => route('builder.show', $this->resource)]);

        return 'https://wa.me/'.app(SoftwareOrderService::class)->ownerWhatsappNumber().'?text='.rawurlencode($text);
    }
}
