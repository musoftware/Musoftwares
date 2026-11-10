<?php

namespace App\Console\Commands;

use App\Mail\BlogArticlePublishedMail;
use App\Models\AdminSettings;
use App\Models\BlogArticle;
use App\Services\BlogAutopilot;
use App\Support\AdminRecipients;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PublishBlogArticle extends Command
{
    protected $signature = 'blog:autopilot {--force : Run even when the blog_autopilot_enabled setting is off}';

    protected $description = 'Write and publish one blog article (Arabic and English in turn) and tell the owner.';

    /** On unless the owner turns it off with blog_autopilot_enabled = 0. */
    public static function enabled(): bool
    {
        return filter_var(AdminSettings::GetValue('blog_autopilot_enabled', '1'), FILTER_VALIDATE_BOOLEAN);
    }

    public function handle(BlogAutopilot $autopilot): int
    {
        if (! self::enabled() && ! $this->option('force')) {
            $this->info('The blog autopilot is off. Set the blog_autopilot_enabled setting to 1 to turn it on.');

            return self::SUCCESS;
        }

        $article = $autopilot->publishNext();
        if ($article === null) {
            $this->warn('No article passed the quality check. The next run tries again.');

            return self::FAILURE;
        }

        $this->tellOwner($article);
        $this->info("Published [{$article->language}] \"{$article->title}\" at /blog/{$article->slug}");

        return self::SUCCESS;
    }

    private function tellOwner(BlogArticle $article): void
    {
        try {
            Mail::to(AdminRecipients::emails())->send(new BlogArticlePublishedMail($article));
        } catch (\Throwable $e) {
            Log::warning('Blog published mail failed.', ['error' => $e->getMessage()]);
        }
    }
}
