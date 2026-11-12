<?php

namespace Modules\AppBuilder\Services;

use Illuminate\Support\Facades\DB;
use Modules\AppBuilder\Models\BuilderMessage;
use Modules\AppBuilder\Models\BuilderProject;
use Modules\AppBuilder\Models\BuilderRequest;

/**
 * A customer brings a project of their own as a Google Drive link and says what they need changed. They pay a small
 * fixed fee, the worker inspects it in a locked box (no internet, nothing else on the build PC) and writes them a plain
 * report, and the maintenance price joins their list of requests. The fee is its own price, and a project we did not
 * build has no warranty.
 */
class ProjectInspection
{
    public const OFFER = 'offer';

    public const RUNNING = 'running';

    public const DONE = 'done';

    public const FAILED = 'failed';

    public function __construct(private readonly ProjectPayment $payment, private readonly QuotePricer $pricer) {}

    public static function price(): float
    {
        return (float) config('builder.inspection.price');
    }

    /**
     * Only a chat that has not started a paid build can bring a project in. The customer may change the link until they
     * pay, and send a new one when the inspection could not open the last.
     */
    public function canOffer(BuilderProject $project): bool
    {
        return ! $project->hasDelivery()
            && in_array($project->status, [BuilderProject::CHATTING, BuilderProject::QUOTED], true)
            && in_array($project->inspection_status, [null, self::OFFER, self::FAILED], true);
    }

    /**
     * The inspection could not finish, most often a link that is not shared with anyone who has it. The fee stays paid:
     * the customer sends the link again and the inspection runs without paying twice. The owner hears the real reason.
     */
    public function failed(BuilderProject $project, string $error): void
    {
        $project->update([
            'inspection_status' => self::FAILED,
            'status' => BuilderProject::CHATTING,
            'job_kind' => BuilderProject::JOB_BUILD,
            'error' => mb_substr($error, 0, 4000),
            'lease_expires_at' => null,
            'progress_step' => null,
        ]);
        $project->messages()->create(['role' => BuilderMessage::ASSISTANT, 'content' => __('builder.inspection.failed', [], $project->customerLanguage())]);
        $this->payment->tellOwner($project, 'Inspection failed: '.$project->title.' '.mb_substr($error, 0, 500));
    }

    public function offer(BuilderProject $project, string $driveUrl, string $need): void
    {
        // already paid (the last link could not be opened): the inspection runs again at once
        $paid = (float) $project->inspection_paid > 0;
        DB::transaction(function () use ($project, $driveUrl, $need, $paid): void {
            $project->update([
                'customer_project' => true,
                'import_url' => $driveUrl,
                'inspection_status' => $paid ? self::RUNNING : self::OFFER,
                'inspection_need' => $need,
                // the inspection reads it as its brief: what the customer asked for
                'spec' => $need,
                'title' => $project->title ?: mb_substr($need, 0, 80),
                // a price from talk before the project came in no longer applies; a paid fee goes straight to the worker
                'status' => $paid ? BuilderProject::QUEUED : BuilderProject::CHATTING,
                'job_kind' => $paid ? BuilderProject::JOB_IMPORT : BuilderProject::JOB_BUILD,
                'attempts' => 0,
                'error' => null,
            ]);
            $project->messages()->create(['role' => BuilderMessage::USER, 'content' => $driveUrl."\n\n".$need]);
            $project->messages()->create([
                'role' => BuilderMessage::ASSISTANT,
                'content' => $paid
                    ? __('builder.inspection.again_reply', [], $project->customerLanguage())
                    : __('builder.inspection.offer_reply', ['price' => number_format(self::price(), 2).' '.$project->currency], $project->customerLanguage()),
            ]);
        });
    }

    /** The fee arrived: the worker downloads and inspects the project. The gateway may report a payment twice. */
    public function markPaid(BuilderProject $project, ?string $reference): bool
    {
        if ($project->inspection_status !== self::OFFER) {
            return false;
        }

        $project->update([
            'inspection_status' => self::RUNNING,
            'inspection_paid' => self::price(),
            'inspection_reference' => $reference,
            'paid_total' => (float) $project->paid_total + self::price(),
            'status' => BuilderProject::QUEUED,
            'job_kind' => BuilderProject::JOB_IMPORT,
            'paid_at' => now(),
            'attempts' => 0,
            'error' => null,
        ]);
        $this->payment->tellOwner($project, 'Inspection paid: '.$project->title.' '.$project->import_url);
        app(ProjectAccount::class)->linkAfterFee($project);
        app(PaymentInvoices::class)->feePaid($project->refresh(), __('builder.invoice.inspection', [], $project->customerLanguage()), self::price());

        return true;
    }

    /**
     * The worker inspected the project: the customer reads the report, and the maintenance work it estimated joins the
     * list with its price, to pay when they like.
     *
     * @param  array{title?: string|null, spec?: string|null, tokens?: int|null, complex?: bool|null}  $estimate
     */
    public function done(BuilderProject $project, string $report, array $estimate): void
    {
        $project->update(['inspection_status' => self::DONE]);
        $project->messages()->create(['role' => BuilderMessage::ASSISTANT, 'content' => trim($report)]);
        if (blank($estimate['title'] ?? null) || (int) ($estimate['tokens'] ?? 0) <= 0) {
            return;
        }

        $price = $this->pricer->change((int) $estimate['tokens'], (bool) ($estimate['complex'] ?? false));
        $project->requests()->create([
            'title' => mb_substr((string) $estimate['title'], 0, 255),
            'spec' => (string) ($estimate['spec'] ?? ''),
            'customer_words' => $project->inspection_need,
            'kind' => BuilderRequest::BUILD,
            'free' => false,
            'complex' => (bool) ($estimate['complex'] ?? false),
            'estimated_tokens' => $price['tokens'],
            'amount' => $price['amount'],
            'budget_usd' => $price['budget'],
            'model' => $price['model'],
            'status' => BuilderRequest::UNPAID,
        ]);
    }
}
