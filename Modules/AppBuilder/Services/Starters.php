<?php

namespace Modules\AppBuilder\Services;

/**
 * Programs Musoftware already built (a clinic system, a POS, a bulk sender...). When a customer asks for one of them,
 * the chat offers it as the starting point: the build machine copies it and develops it into the customer's idea
 * instead of writing everything from nothing. The cards live in Resources/starters.json; the code itself lives only on
 * the build machine. Each card's picture is public/uploads/builder-starters/<slug>.webp, taken once.
 */
class Starters
{
    private const IMAGE_FOLDER = 'uploads/builder-starters';

    /** @var list<array<string, mixed>>|null */
    private ?array $cards = null;

    /** @return list<array<string, mixed>> */
    public function all(): array
    {
        if ($this->cards !== null) {
            return $this->cards;
        }
        $file = (string) (config('builder.starters_file') ?: module_path('AppBuilder', 'Resources/starters.json'));
        $cards = is_file($file) ? json_decode((string) file_get_contents($file), true) : [];

        return $this->cards = array_values(array_filter(is_array($cards) ? $cards : [], fn ($card) => is_array($card) && isset($card['slug'])));
    }

    /** @return list<string> */
    public function slugs(): array
    {
        return array_column($this->all(), 'slug');
    }

    public function find(?string $slug): ?array
    {
        return $slug ? (collect($this->all())->firstWhere('slug', $slug)) : null;
    }

    /** What the customer sees under the message that offered it, in their language. */
    public function card(?string $slug, string $language): ?array
    {
        $starter = $this->find($slug);
        if (! $starter) {
            return null;
        }
        $suffix = $language === 'ar' ? '_ar' : '_en';
        $image = self::IMAGE_FOLDER.'/'.$starter['slug'].'.webp';

        return [
            'slug' => $starter['slug'],
            'title' => $starter['title'.$suffix] ?? $starter['title_en'],
            'summary' => $starter['summary'.$suffix] ?? $starter['summary_en'],
            'features' => array_slice((array) ($starter['features'.$suffix] ?? $starter['features_en'] ?? []), 0, 8),
            'image' => is_file(public_path($image)) ? asset($image) : null,
        ];
    }

    /** The list the chat model reads, one line each. */
    public function forPrompt(): string
    {
        return collect($this->all())->map(fn (array $starter) => sprintf(
            '- `%s` (%s): %s Features: %s.',
            $starter['slug'],
            $starter['type'] ?? 'other',
            $starter['summary_en'] ?? '',
            implode(', ', (array) ($starter['features_en'] ?? [])),
        ))->implode("\n");
    }
}
