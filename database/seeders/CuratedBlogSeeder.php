<?php

namespace Database\Seeders;

use App\Models\BlogArticle;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Ramsey\Uuid\Uuid;

/**
 * Hand-written blog articles: 6 in Arabic and English versions of 3 of them (same group_id).
 * Safe to run again: each article is found by its slug and updated; a soft-deleted one is restored.
 * Nothing is hard-deleted. Run `php artisan marketplace:generate-ai-files` afterwards to refresh llms.txt.
 */
class CuratedBlogSeeder extends Seeder
{
    /** Articles are dated back from this day so the blog does not show one burst of posts. */
    private const LAST_DAY = '2026-10-24 09:00';

    public function run(): void
    {
        BlogArticle::withoutEvents(function () {
            foreach (CuratedBlogContentAr::articles() as $article) {
                $this->store($article, 'ar');
            }
            foreach (CuratedBlogContentEn::articles() as $article) {
                $this->store($article, 'en');
            }
        });
    }

    private function store(array $article, string $language): void
    {
        $record = BlogArticle::withTrashed()->where('slug', $article['slug'])->first() ?? new BlogArticle;

        $record->fill([
            'service_id' => null,
            'language' => $language,
            'group_id' => Uuid::uuid5(Uuid::NAMESPACE_URL, 'https://www.musoftwares.com/blog/group/'.$article['key'])->toString(),
            'title' => $article['title'],
            'slug' => $article['slug'],
            'content' => $article['content'],
            'excerpt' => $article['excerpt'],
            'meta_title' => $article['meta_title'],
            'meta_description' => $article['meta_description'],
            'is_published' => true,
            'published_at' => Carbon::parse(self::LAST_DAY, 'Africa/Cairo')->subDays($article['days_ago']),
        ]);
        $record->deleted_at = null;
        $record->save();
    }
}
