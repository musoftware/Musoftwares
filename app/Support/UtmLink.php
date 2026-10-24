<?php

namespace App\Support;

/** Adds utm_* parameters to a link so a visit can be traced back to the post, message or share button that sent it. */
final class UtmLink
{
    /** channel => [utm_source, utm_medium] */
    public const CHANNELS = [
        'facebook' => ['facebook', 'social'],
        'whatsapp' => ['whatsapp', 'message'],
        'linkedin' => ['linkedin', 'social'],
        'x' => ['x', 'social'],
        'email' => ['email', 'email'],
        'groups' => ['facebook_groups', 'community'],
        'copy' => ['copy_link', 'share'],
    ];

    public static function make(string $url, string $channel, string $campaign): string
    {
        [$source, $medium] = self::CHANNELS[$channel] ?? [$channel, 'link'];
        $separator = str_contains($url, '?') ? '&' : '?';

        return $url.$separator.http_build_query(['utm_source' => $source, 'utm_medium' => $medium, 'utm_campaign' => $campaign]);
    }
}
