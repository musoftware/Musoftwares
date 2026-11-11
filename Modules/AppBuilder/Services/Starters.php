<?php

namespace Modules\AppBuilder\Services;

use Modules\AppBuilder\Models\BuilderStarter;

/**
 * Programs Musoftware already built (a clinic system, a POS, a bulk sender...). When a customer asks for one of them,
 * the chat offers it as the starting point: the build machine copies it and develops it into the customer's idea
 * instead of writing everything from nothing. The cards live in Resources/starters.json; the code itself lives only on
 * the build machine. Each card's picture is public/uploads/builder-starters/<slug>.webp, taken once.
 * Programs learned from paid deliveries (BuilderStarter) join the list as internal ones: the chat uses them for the
 * known price and the build starts from them, but the customer is never shown or told.
 */
class Starters
{
    private const IMAGE_FOLDER = 'uploads/builder-starters';

    /** The newest learned programs the chat reads; older ones stay in the table. */
    private const LEARNED_IN_PROMPT = 100;

    /** @var list<array<string, mixed>>|null */
    private ?array $cards = null;

    /** @return list<array<string, mixed>> */
    public function all(): array
    {
        if ($this->cards !== null) {
            return $this->cards;
        }
        $listed = $this->read((string) (config('builder.starters_file') ?: module_path('AppBuilder', 'Resources/starters.json')));
        // free ready designs: a customer without a design picks one from pictures, and the page starts from its files
        $designs = array_map(fn (array $card) => $card + ['design' => true, 'type' => 'page'],
            $this->read((string) (config('builder.designs_file') ?: module_path('AppBuilder', 'Resources/designs.json'))));
        $learned = BuilderStarter::with('source')->latest('id')->limit(self::LEARNED_IN_PROMPT)->get()->map->toCard()->all();

        return $this->cards = [...$listed, ...$designs, ...$learned];
    }

    /** @return list<array<string, mixed>> */
    private function read(string $file): array
    {
        $cards = is_file($file) ? json_decode((string) file_get_contents($file), true) : [];

        return array_values(array_filter(is_array($cards) ? $cards : [], fn ($card) => is_array($card) && isset($card['slug'])));
    }

    /** @return list<string> */
    public function slugs(): array
    {
        return array_column($this->all(), 'slug');
    }

    /** @return list<string> */
    public function designSlugs(): array
    {
        return array_column(array_filter($this->all(), fn (array $card) => ! empty($card['design'])), 'slug');
    }

    public function find(?string $slug): ?array
    {
        return $slug ? (collect($this->all())->firstWhere('slug', $slug)) : null;
    }

    /** The price a learned program was sold for: a matching quote never goes below it. */
    public function knownPrice(?string $slug): float
    {
        return (float) ($this->find($slug)['price'] ?? 0);
    }

    /** What the customer sees under the message that offered it, in their language; nothing for an internal one. */
    public function card(?string $slug, string $language): ?array
    {
        $starter = $this->find($slug);
        if (! $starter || ! empty($starter['internal'])) {
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

    /** The free designs the chat model reads, one line each. */
    public function designsForPrompt(): string
    {
        return collect($this->all())->filter(fn (array $card) => ! empty($card['design']))->map(fn (array $design) => sprintf(
            '- `%s` (%s): %s Sections: %s.',
            $design['slug'],
            $design['category'] ?? 'any',
            $design['summary_en'] ?? '',
            implode(', ', (array) ($design['features_en'] ?? [])),
        ))->implode("\n");
    }

    /** The ready programs the chat model reads, one line each. */
    public function forPrompt(): string
    {
        return collect($this->all())->reject(fn (array $card) => ! empty($card['design']))->map(fn (array $starter) => sprintf(
            '- `%s` (%s%s): %s Features: %s.',
            $starter['slug'],
            $starter['type'] ?? 'other',
            empty($starter['internal']) ? '' : sprintf('; internal, never mention it; the price starts at %s %s', $starter['price'], $starter['currency']),
            $starter['summary_en'] ?? '',
            implode(', ', (array) ($starter['features_en'] ?? [])),
        ))->implode("\n");
    }
}
