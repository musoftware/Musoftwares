<?php

namespace Modules\AppBuilder\Services;

use App\Support\ArabicShaper;
use GdImage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Modules\AppBuilder\Models\BuilderProject;
use RuntimeException;

/**
 * The picture shown when the /ai page is shared. It is drawn on the server once a day with the current number of
 * programs built, saved under a new name each time so Facebook and WhatsApp fetch the new one instead of keeping
 * the old copy, and the older pictures are removed.
 */
class AiPageImage
{
    private const WIDTH = 1200;

    private const HEIGHT = 630;

    private const FOLDER = 'images/og';

    private const FONT = 'v8main/font/29LTBukra-Bold.ttf';

    private const LOCALES = ['ar', 'en'];

    /** The address of today's picture for this language, drawn now if there is none yet. */
    public function url(string $locale): string
    {
        $locale = in_array($locale, self::LOCALES, true) ? $locale : 'ar';
        $name = Cache::get($this->cacheKey($locale));
        if (! is_string($name) || ! is_file(public_path(self::FOLDER.'/'.$name))) {
            $name = $this->draw($locale);
        }

        return asset(self::FOLDER.'/'.$name);
    }

    /** Draws a fresh picture in every language; run once a day. */
    public function refresh(): void
    {
        foreach (self::LOCALES as $locale) {
            $this->draw($locale);
        }
    }

    private function draw(string $locale): string
    {
        $name = 'ai-'.$locale.'-'.now()->format('YmdHis').'.png';
        File::ensureDirectoryExists(public_path(self::FOLDER));
        $image = $this->picture($locale);
        imagepng($image, public_path(self::FOLDER.'/'.$name), 6);

        $this->removeOld($locale, $name);
        Cache::forever($this->cacheKey($locale), $name);

        return $name;
    }

    private function picture(string $locale): GdImage
    {
        $image = imagecreatetruecolor(self::WIDTH, self::HEIGHT) ?: throw new RuntimeException('GD could not make the /ai picture');
        imagefill($image, 0, 0, imagecolorallocate($image, 245, 245, 247));
        $rtl = $locale === 'ar';
        $this->chatBubbles($image, $rtl);

        $ink = imagecolorallocate($image, 29, 29, 31);
        $muted = imagecolorallocate($image, 110, 110, 115);
        $blue = imagecolorallocate($image, 0, 113, 227);
        // each paragraph starts one and a half of its own line heights under the one before
        $y = $this->paragraph($image, __('builder.og_headline', [], $locale), 52, 160, $ink, $rtl);
        $y = $this->paragraph($image, __('builder.og_sub', [], $locale), 26, $y + 60, $muted, $rtl);
        $this->paragraph($image, __('builder.og_count', ['count' => number_format($this->programsBuilt())], $locale), 32, $y + 75, $blue, $rtl);
        $this->line($image, 'musoftwares.com/ai', 26, 540, $ink, $rtl);

        return $image;
    }

    /** A small chat on the side, so the picture says "you talk, we build" before anyone reads it. */
    private function chatBubbles(GdImage $image, bool $rtl): void
    {
        $white = imagecolorallocate($image, 255, 255, 255);
        $blue = imagecolorallocate($image, 0, 113, 227);
        $grey = imagecolorallocate($image, 229, 229, 234);
        $left = $rtl ? 70 : self::WIDTH - 410;

        $this->roundBox($image, $left, 130, $left + 340, 500, 28, $white);
        $this->roundBox($image, $left + 120, 170, $left + 310, 220, 22, $blue);
        $this->roundBox($image, $left + 30, 245, $left + 260, 295, 22, $grey);
        $this->roundBox($image, $left + 30, 315, $left + 220, 365, 22, $grey);
        $this->roundBox($image, $left + 150, 390, $left + 310, 440, 22, $blue);
    }

    private function roundBox(GdImage $image, int $x1, int $y1, int $x2, int $y2, int $radius, int $color): void
    {
        imagefilledrectangle($image, $x1 + $radius, $y1, $x2 - $radius, $y2, $color);
        imagefilledrectangle($image, $x1, $y1 + $radius, $x2, $y2 - $radius, $color);
        foreach ([[$x1 + $radius, $y1 + $radius], [$x2 - $radius, $y1 + $radius], [$x1 + $radius, $y2 - $radius], [$x2 - $radius, $y2 - $radius]] as [$cx, $cy]) {
            imagefilledellipse($image, $cx, $cy, $radius * 2, $radius * 2, $color);
        }
    }

    /** The text column sits beside the chat drawing and is this wide. */
    private const TEXT_WIDTH = 620;

    /** Text broken into lines that fit the column. @return int where the last line ended */
    private function paragraph(GdImage $image, string $text, int $size, int $y, int $color, bool $rtl): int
    {
        $lines = [];
        foreach (preg_split('/\s+/u', trim($text)) ?: [] as $word) {
            $last = array_key_last($lines);
            $longer = $last === null ? $word : $lines[$last].' '.$word;
            if ($last !== null && $this->width($longer, $size) <= self::TEXT_WIDTH) {
                $lines[$last] = $longer;
            } else {
                $lines[] = $word;
            }
        }

        foreach ($lines as $index => $line) {
            $this->line($image, $line, $size, $y + (int) ($index * $size * 1.5), $color, $rtl);
        }

        return $y + (int) ((count($lines) - 1) * $size * 1.5);
    }

    /** One line in the text column: right-aligned for Arabic, left-aligned for English. */
    private function line(GdImage $image, string $text, int $size, int $y, int $color, bool $rtl): void
    {
        $x = $rtl ? self::WIDTH - 80 - $this->width($text, $size) : 80;
        imagettftext($image, $size, 0, (int) $x, $y, $color, public_path(self::FONT), ArabicShaper::forImage($text));
    }

    private function width(string $text, int $size): int
    {
        $box = imagettfbbox($size, 0, public_path(self::FONT), ArabicShaper::forImage($text)) ?: [0, 0, 0, 0, 0, 0, 0, 0];

        return abs($box[2] - $box[0]);
    }

    private function programsBuilt(): int
    {
        return BuilderProject::whereNotNull('built_at')->count();
    }

    private function removeOld(string $locale, string $keep): void
    {
        foreach (File::glob(public_path(self::FOLDER.'/ai-'.$locale.'-*.png')) as $old) {
            if (basename($old) !== $keep) {
                File::delete($old);
            }
        }
    }

    private function cacheKey(string $locale): string
    {
        return 'builder:og-image:'.$locale;
    }
}
