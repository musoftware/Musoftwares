<?php

namespace Modules\AppBuilder\Services\Study;

use Illuminate\Support\Facades\Log;
use Modules\AppBuilder\Models\BuilderMessage;
use Modules\AppBuilder\Models\BuilderProject;

/**
 * A short feasibility study for a serious customer: once the scope of a bigger project is agreed, the strong
 * model writes it in the customer's language, and what it cost (times the markup) is added to the price.
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

    /** A study is offered once per project, for the kinds of work where planning matters. */
    public function isDue(BuilderProject $project): bool
    {
        return $project->study_status === null
            && in_array($project->project_type, (array) config('builder.study.for_types'), true);
    }

    public function start(BuilderProject $project): void
    {
        $project->update(['study_status' => self::WRITING]);
        // written after the customer already has the reply (the app's terminating step runs after the response
        // is sent), so the chat never waits for it; nothing is serialized, so no queue worker is needed
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
            $fee = $this->fee($result['input_tokens'], $result['output_tokens']);
            $project->update([
                'study_status' => self::READY,
                'study_html' => $this->clean($result['html']),
                'study_fee' => $fee,
                'amount' => (float) $project->amount + $fee,
            ]);
        } catch (\Throwable $e) {
            Log::warning('App builder: feasibility study failed', ['project_id' => $project->id, 'error' => $e->getMessage()]);
            $project->update(['study_status' => self::FAILED]);
        }
    }

    /** What the study cost on the strong model, times the markup, never less than 1. */
    private function fee(int $input, int $output): float
    {
        $study = config('builder.study');
        $cost = ($input * $study['input_usd_per_mtok'] + $output * $study['output_usd_per_mtok']) / 1_000_000;

        return max(1.0, ceil($cost * (float) config('builder.pricing.markup')));
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
