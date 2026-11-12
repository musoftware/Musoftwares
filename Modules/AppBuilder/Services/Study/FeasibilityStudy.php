<?php

namespace Modules\AppBuilder\Services\Study;

use Illuminate\Support\Facades\Log;
use Modules\AppBuilder\Models\BuilderMessage;
use Modules\AppBuilder\Models\BuilderProject;
use Modules\AppBuilder\Services\PriceRange;
use Modules\AppBuilder\Services\ProjectPayment;

/**
 * A full feasibility study for a bigger project. It is offered next to the price for a small fixed amount and the
 * strong model writes it only after the customer pays for it. What they paid comes off the project's first price.
 * The customer reads it on its own page and saves it as a PDF from the browser, which shapes Arabic correctly.
 */
class FeasibilityStudy
{
    public const WRITING = 'writing';

    public const READY = 'ready';

    public const FAILED = 'failed';

    /** Only safe structural tags survive; every attribute is removed. */
    private const ALLOWED_TAGS = '<h2><h3><p><ul><ol><li><table><thead><tbody><tr><th><td><strong><em><br>';

    public function __construct(private readonly StudyWriter $writer) {}

    public static function price(): float
    {
        return (float) config('builder.study.price');
    }

    /** Offered with an open price for the kinds of work where planning matters, once, before the first build. */
    public function canBuy(BuilderProject $project): bool
    {
        return $project->status === BuilderProject::QUOTED
            && ! $project->free_fix
            && ! $project->hasDelivery()
            && $project->study_status === null
            && (float) $project->study_paid <= 0
            && self::price() > 0
            && in_array($project->project_type, (array) config('builder.study.for_types'), true);
    }

    /**
     * The study was paid: it is written now, and an open price that is not paid yet goes down by what was paid.
     * The gateway may report one payment twice; a study already paid is never counted again.
     */
    public function markPaid(BuilderProject $project, ?string $reference): bool
    {
        if ((float) $project->study_paid > 0) {
            return false;
        }

        $paid = self::price();
        $project->update([
            'study_paid' => $paid,
            'study_payment_reference' => $reference,
            'paid_total' => (float) $project->paid_total + $paid,
        ]);
        $this->lowerOpenPrice($project, $paid);
        app(ProjectPayment::class)->tellOwner($project, 'Study paid: '.$project->title.' ('.$paid.' '.$project->currency.')');
        $this->start($project);

        return true;
    }

    private function lowerOpenPrice(BuilderProject $project, float $paid): void
    {
        if ($project->status !== BuilderProject::QUOTED || (float) $project->quote_paid > 0 || $project->hasDelivery()) {
            return;
        }

        $amount = max(1.0, (float) $project->amount - $paid);
        $project->update(['amount' => $amount, 'payment_due' => 0, 'price_max' => PriceRange::around($amount)['to']]);
    }

    private function start(BuilderProject $project): void
    {
        $project->update(['study_status' => self::WRITING]);
        // written after the answer to the payment is sent (the app's terminating step), so nothing waits for it;
        // nothing is serialized, so no queue worker is needed
        $id = $project->id;
        app()->terminating(function () use ($id): void {
            $fresh = BuilderProject::find($id);
            if ($fresh) {
                $this->write($fresh);
            }
        });
    }

    public function write(BuilderProject $project): void
    {
        try {
            $result = $this->writer->write($this->systemPrompt(), $this->brief($project));
            $project->update(['study_status' => self::READY, 'study_html' => $this->clean($result['html'])]);
        } catch (\Throwable $e) {
            // the customer paid for it, so the owner hears at once; what they paid still comes off the project
            Log::warning('App builder: feasibility study failed', ['project_id' => $project->id, 'error' => $e->getMessage()]);
            $project->update(['study_status' => self::FAILED]);
            app(ProjectPayment::class)->tellOwner($project, 'Paid study failed to write: '.$project->title);
        }
    }

    private function brief(BuilderProject $project): string
    {
        $chat = $project->messages()->whereIn('role', [BuilderMessage::USER, BuilderMessage::ASSISTANT, BuilderMessage::TEAM])->get()
            ->map(fn (BuilderMessage $m) => ($m->role === BuilderMessage::USER ? 'Customer: ' : 'Musoftware: ').$m->content)
            ->implode("\n\n");

        return "Project: {$project->title}\nCustomer language: ".($project->customerLanguage() === 'ar' ? 'Arabic (Egyptian)' : 'English')
            ."\n\n## Agreed build brief\n\n{$project->spec}\n\n## The chat with the customer\n\n{$chat}";
    }

    private function systemPrompt(): string
    {
        return (string) file_get_contents(module_path('AppBuilder', 'Resources/prompts/study.md'));
    }

    private function clean(string $html): string
    {
        $html = preg_replace('/```(?:html)?|```/i', '', $html) ?? '';
        // script and style blocks go with their content, not just their tags
        $html = preg_replace('#<(script|style|iframe|object)\b[^>]*>.*?</\1\s*>#is', '', $html) ?? '';
        $html = strip_tags($html, self::ALLOWED_TAGS);

        return preg_replace('/<(\w+)\s[^>]*>/', '<$1>', $html) ?? '';
    }
}
