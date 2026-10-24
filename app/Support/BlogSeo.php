<?php

namespace App\Support;

use App\Models\BlogArticle;
use Illuminate\Support\Collection;

/** Structured data, translations and reading time for the public blog. */
class BlogSeo
{
    private const WORDS_PER_MINUTE = 180;

    public function readingMinutes(BlogArticle $article): int
    {
        $text = trim(strip_tags((string) $article->content));
        $words = $text === '' ? 0 : count(preg_split('/\s+/u', $text) ?: []);

        return max(1, (int) ceil($words / self::WORDS_PER_MINUTE));
    }

    /** @return Collection<string, string> language => article url, for the article and its published translations */
    public function alternates(BlogArticle $article): Collection
    {
        $group = empty($article->group_id)
            ? collect([$article])
            : BlogArticle::published()->where('group_id', $article->group_id)->get();

        return $group->mapWithKeys(fn (BlogArticle $item) => [$item->language => route('blog.show', $item->slug)]);
    }

    /** @return array<string, mixed> */
    public function articleGraph(BlogArticle $article, ?string $image): array
    {
        $url = route('blog.show', $article->slug);
        $organization = ['@type' => 'Organization', 'name' => 'Musoftware', 'url' => url('/')];

        return [
            '@context' => 'https://schema.org',
            '@graph' => [
                array_filter([
                    '@type' => 'Article',
                    'headline' => mb_substr((string) $article->title, 0, 110),
                    'description' => $article->meta_description ?: $article->excerpt,
                    'inLanguage' => $article->language,
                    'mainEntityOfPage' => $url,
                    'url' => $url,
                    'image' => $image,
                    'datePublished' => optional($article->published_at ?? $article->created_at)->toAtomString(),
                    'dateModified' => optional($article->updated_at)->toAtomString(),
                    'author' => ['@type' => 'Person', 'name' => 'Mahmoud Amin', 'url' => url('/about/mahmoud-amin')],
                    'publisher' => $organization,
                ]),
                [
                    '@type' => 'BreadcrumbList',
                    'itemListElement' => [
                        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Musoftware', 'item' => url('/')],
                        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Blog', 'item' => route('blog.index')],
                        ['@type' => 'ListItem', 'position' => 3, 'name' => $article->title, 'item' => $url],
                    ],
                ],
            ],
        ];
    }

    public function toJson(array $graph): string
    {
        return json_encode($graph, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG);
    }
}
