<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\ArabicShaper;
use GdImage;
use RuntimeException;

/**
 * The picture shown when a badge card link is shared: the badge, the level, the client's first name and the brand,
 * drawn on the server at the size social apps use for link previews (1200 x 630).
 */
final class BadgeCardImage
{
    private const WIDTH = 1200;

    private const HEIGHT = 630;

    private const BADGE_SIZE = 360;

    private const FONT = 'v8main/font/29LTBukra-Bold.ttf';

    /** @param array{first_name: string, tier_slug: string, tier_name: string, color: string, member_since: ?string} $card */
    public function png(array $card, string $locale): string
    {
        $image = imagecreatetruecolor(self::WIDTH, self::HEIGHT) ?: throw new RuntimeException('GD could not make the badge image');
        imagefill($image, 0, 0, $this->tint($image, $card['color'], 0.14));
        $this->drawPanel($image);
        $rtl = $locale === 'ar';
        $this->drawBadge($image, $card['tier_slug'], $rtl);
        $this->drawText($image, $card, $locale, $rtl);

        ob_start();
        imagepng($image, null, 6);

        return (string) ob_get_clean();
    }

    private function drawPanel(GdImage $image): void
    {
        $white = imagecolorallocate($image, 255, 255, 255);
        imagefilledrectangle($image, 40, 40, self::WIDTH - 40, self::HEIGHT - 40, $white);
    }

    private function drawBadge(GdImage $image, string $slug, bool $rtl): void
    {
        $badge = @imagecreatefrompng(public_path("images/tiers/{$slug}.png"));
        if ($badge === false) {
            return;
        }

        $x = $rtl ? 100 : self::WIDTH - 100 - self::BADGE_SIZE;
        imagecopyresampled($image, $badge, $x, (self::HEIGHT - self::BADGE_SIZE) / 2, 0, 0, self::BADGE_SIZE, self::BADGE_SIZE, imagesx($badge), imagesy($badge));
    }

    /** @param array{first_name: string, tier_name: string, color: string, member_since: ?string} $card */
    private function drawText(GdImage $image, array $card, string $locale, bool $rtl): void
    {
        $ink = imagecolorallocate($image, 29, 29, 31);
        $muted = imagecolorallocate($image, 110, 110, 115);
        $lines = array_filter([
            [__('badge_card.headline', ['tier' => $card['tier_name']], $locale), 58, 210, $ink],
            [$card['first_name'], 44, 290, $ink],
            [$card['member_since'] ? __('badge_card.member_since', ['year' => $card['member_since']], $locale) : '', 28, 350, $muted],
            [__('badge_card.brand', [], $locale), 34, 500, $this->tint($image, $card['color'], 1.0)],
        ], fn (array $line) => trim($line[0]) !== '');

        foreach ($lines as [$text, $size, $y, $color]) {
            $this->line($image, $text, $size, $y, $color, $rtl);
        }
    }

    /** One line in the text column: right-aligned for Arabic, left-aligned for English. */
    private function line(GdImage $image, string $text, int $size, int $y, int $color, bool $rtl): void
    {
        $font = public_path(self::FONT);
        $drawn = ArabicShaper::forImage(mb_strimwidth($text, 0, 40, '...'));
        $box = imagettfbbox($size, 0, $font, $drawn) ?: [0, 0, 0, 0, 0, 0, 0, 0];
        $width = abs($box[2] - $box[0]);
        $x = $rtl ? self::WIDTH - 110 - $width : 110;
        imagettftext($image, $size, 0, (int) $x, $y, $color, $font, $drawn);
    }

    /** The level color mixed with white: 0 is white, 1 is the color itself. */
    private function tint(GdImage $image, string $hex, float $amount): int
    {
        [$r, $g, $b] = sscanf($hex, '#%02x%02x%02x') ?: [205, 127, 50];
        $mix = fn (int $channel) => (int) round(255 - (255 - $channel) * $amount);

        return imagecolorallocate($image, $mix($r), $mix($g), $mix($b));
    }
}
