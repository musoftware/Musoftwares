<?php

namespace Modules\AppBuilder\Services;

/**
 * What the engineer tried before a delivery, as the customer reads it under the delivery in the chat: one plain line
 * per thing and whether it works. The worker sends it as JSON; here it is cleaned and kept small.
 */
class DeliveryChecks
{
    private const MAX_ROWS = 10;

    private const MAX_WHAT_CHARS = 160;

    private const MAX_NOTE_CHARS = 200;

    /** @return list<array{what: string, passed: bool, note: string}> */
    public static function fromJson(string $json): array
    {
        $rows = json_decode($json, true);
        if (! is_array($rows)) {
            return [];
        }

        return collect($rows)
            ->filter(fn ($row) => is_array($row) && self::plain($row['what'] ?? '', self::MAX_WHAT_CHARS) !== '')
            ->take(self::MAX_ROWS)
            ->map(fn (array $row) => [
                'what' => self::plain($row['what'], self::MAX_WHAT_CHARS),
                // only a clear yes counts as working
                'passed' => ($row['passed'] ?? false) === true,
                'note' => self::plain($row['note'] ?? '', self::MAX_NOTE_CHARS),
            ])
            ->values()
            ->all();
    }

    private static function plain(mixed $text, int $max): string
    {
        return is_string($text) ? mb_substr(trim(strip_tags($text)), 0, $max) : '';
    }
}
