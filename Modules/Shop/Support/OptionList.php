<?php

namespace Modules\Shop\Support;

final class OptionList
{
    /**
     * Turns "40, 41 | 42" into ["40", "41", "42"].
     *
     * @return list<string>
     */
    public static function parse(mixed $text): array
    {
        $parts = preg_split('/[,،|]+/u', is_string($text) ? $text : '') ?: [];

        return array_values(array_unique(array_filter(array_map('trim', $parts), fn (string $part) => $part !== '')));
    }
}
