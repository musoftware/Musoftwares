<?php

declare(strict_types=1);

namespace App\Support;

/**
 * GD draws letters one by one, left to right, so Arabic comes out as separate, reversed letters.
 * This turns Arabic text into the joined letter forms (Unicode presentation forms) in the order GD draws,
 * so a server-made image shows a name the way people read it. Latin words and digits keep their order.
 */
final class ArabicShaper
{
    /** letter => [isolated, final, initial, medial]; a letter with only two forms never joins to the next one. */
    private const FORMS = [
        'ء' => ['ﺀ'], 'آ' => ['ﺁ', 'ﺂ'], 'أ' => ['ﺃ', 'ﺄ'], 'ؤ' => ['ﺅ', 'ﺆ'], 'إ' => ['ﺇ', 'ﺈ'],
        'ئ' => ['ﺉ', 'ﺊ', 'ﺋ', 'ﺌ'], 'ا' => ['ﺍ', 'ﺎ'], 'ب' => ['ﺏ', 'ﺐ', 'ﺑ', 'ﺒ'], 'ة' => ['ﺓ', 'ﺔ'],
        'ت' => ['ﺕ', 'ﺖ', 'ﺗ', 'ﺘ'], 'ث' => ['ﺙ', 'ﺚ', 'ﺛ', 'ﺜ'], 'ج' => ['ﺝ', 'ﺞ', 'ﺟ', 'ﺠ'],
        'ح' => ['ﺡ', 'ﺢ', 'ﺣ', 'ﺤ'], 'خ' => ['ﺥ', 'ﺦ', 'ﺧ', 'ﺨ'], 'د' => ['ﺩ', 'ﺪ'], 'ذ' => ['ﺫ', 'ﺬ'],
        'ر' => ['ﺭ', 'ﺮ'], 'ز' => ['ﺯ', 'ﺰ'], 'س' => ['ﺱ', 'ﺲ', 'ﺳ', 'ﺴ'], 'ش' => ['ﺵ', 'ﺶ', 'ﺷ', 'ﺸ'],
        'ص' => ['ﺹ', 'ﺺ', 'ﺻ', 'ﺼ'], 'ض' => ['ﺽ', 'ﺾ', 'ﺿ', 'ﻀ'], 'ط' => ['ﻁ', 'ﻂ', 'ﻃ', 'ﻄ'],
        'ظ' => ['ﻅ', 'ﻆ', 'ﻇ', 'ﻈ'], 'ع' => ['ﻉ', 'ﻊ', 'ﻋ', 'ﻌ'], 'غ' => ['ﻍ', 'ﻎ', 'ﻏ', 'ﻐ'],
        'ف' => ['ﻑ', 'ﻒ', 'ﻓ', 'ﻔ'], 'ق' => ['ﻕ', 'ﻖ', 'ﻗ', 'ﻘ'], 'ك' => ['ﻙ', 'ﻚ', 'ﻛ', 'ﻜ'],
        'ل' => ['ﻝ', 'ﻞ', 'ﻟ', 'ﻠ'], 'م' => ['ﻡ', 'ﻢ', 'ﻣ', 'ﻤ'], 'ن' => ['ﻥ', 'ﻦ', 'ﻧ', 'ﻨ'],
        'ه' => ['ﻩ', 'ﻪ', 'ﻫ', 'ﻬ'], 'و' => ['ﻭ', 'ﻮ'], 'ى' => ['ﻯ', 'ﻰ'], 'ي' => ['ﻱ', 'ﻲ', 'ﻳ', 'ﻴ'],
        'ـ' => ['ـ', 'ـ', 'ـ', 'ـ'],
    ];

    /** lam followed by an alef is one letter: [isolated, final] */
    private const LAM_ALEF = ['آ' => ['ﻵ', 'ﻶ'], 'أ' => ['ﻷ', 'ﻸ'], 'إ' => ['ﻹ', 'ﻺ'], 'ا' => ['ﻻ', 'ﻼ']];

    public static function forImage(string $text): string
    {
        if (preg_match('/\p{Arabic}/u', $text) !== 1) {
            return $text;
        }

        $shaped = self::join(mb_str_split($text));

        return self::visualOrder($shaped);
    }

    /** @param  list<string>  $letters */
    private static function join(array $letters): string
    {
        $out = '';
        $count = count($letters);
        for ($i = 0; $i < $count; $i++) {
            $joinsBefore = $i > 0 && self::joinsNext($letters[$i - 1]);
            if ($letters[$i] === 'ل' && isset(self::LAM_ALEF[$letters[$i + 1] ?? ''])) {
                $out .= self::LAM_ALEF[$letters[++$i]][$joinsBefore ? 1 : 0];

                continue;
            }
            $out .= self::form($letters[$i], $joinsBefore, self::joinsNext($letters[$i]) && isset(self::FORMS[$letters[$i + 1] ?? '']));
        }

        return $out;
    }

    private static function form(string $letter, bool $joinsBefore, bool $joinsAfter): string
    {
        $forms = self::FORMS[$letter] ?? null;
        if ($forms === null) {
            return $letter;
        }

        $index = match (true) {
            $joinsBefore && $joinsAfter => 3,
            $joinsAfter => 2,
            $joinsBefore => 1,
            default => 0,
        };

        return $forms[$index] ?? $forms[$index === 3 ? 1 : 0];
    }

    /** A letter joins the one after it only when it has an initial form. */
    private static function joinsNext(string $letter): bool
    {
        return count(self::FORMS[$letter] ?? []) === 4;
    }

    /** Right-to-left: the whole line is reversed, then each run of Latin letters or digits is turned back. */
    private static function visualOrder(string $text): string
    {
        $reversed = implode('', array_reverse(mb_str_split($text)));

        return (string) preg_replace_callback('/[A-Za-z0-9@.+#_\-]+(?:[ ][A-Za-z0-9@.+#_\-]+)*/u', fn (array $run) => strrev($run[0]), $reversed);
    }
}
