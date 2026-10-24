<?php

namespace App\Console\Commands;

use App\Mail\BlogDraftReadyMail;
use App\Models\AdminSettings;
use App\Services\BlogDraftService;
use App\Support\AdminRecipients;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class DraftWeeklyBlogArticle extends Command
{
    protected $signature = 'blog:draft-weekly {--force : Run even when the blog_drafts_enabled setting is off}';

    protected $description = 'Write this week\'s blog draft (Arabic and English) from the topic queue. The draft is never published.';

    public static function enabled(): bool
    {
        return filter_var(AdminSettings::GetValue('blog_drafts_enabled', false), FILTER_VALIDATE_BOOLEAN);
    }

    public function handle(BlogDraftService $drafts): int
    {
        if (! self::enabled() && ! $this->option('force')) {
            $this->info('Weekly blog drafts are off. Set the blog_drafts_enabled setting to 1 to turn them on.');

            return self::SUCCESS;
        }

        $result = $drafts->createWeeklyDraft();

        if ($result === null) {
            $this->info('Every topic in config/blog_topics.php is already used. Add more topics.');

            return self::SUCCESS;
        }
        if ($result['articles'] === []) {
            $this->warn("No draft passed the quality check for \"{$result['topic']['key']}\". It will be tried again next week.");

            return self::FAILURE;
        }

        $this->notifyOwner(collect($result['articles']));
        $this->info('Saved '.count($result['articles'])." draft(s) for \"{$result['topic']['key']}\". Nothing was published.");

        return self::SUCCESS;
    }

    private function notifyOwner(Collection $articles): void
    {
        try {
            Mail::to(AdminRecipients::emails())->send(new BlogDraftReadyMail($articles));
        } catch (\Throwable $e) {
            Log::warning('Blog draft mail failed.', ['error' => $e->getMessage()]);
        }
    }
}
