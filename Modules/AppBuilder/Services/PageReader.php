<?php

namespace Modules\AppBuilder\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Reads a public web page from this server when the worker's browser is not there. It sees only the HTML the
 * server sends (not what scripts draw later), and it never opens a private or local address, so a customer's
 * link cannot reach the server's own network.
 */
class PageReader
{
    private const MAX_REDIRECTS = 3;

    private const MAX_BYTES = 2_000_000;

    private const TEXT_CHARS = 6000;

    /** @return array{title: string, description: string, text: string} */
    public function read(string $url): array
    {
        for ($redirect = 0; $redirect <= self::MAX_REDIRECTS; $redirect++) {
            $response = $this->fetch($url);
            if (! $response->redirect()) {
                return $this->contentOf((string) $response->body());
            }
            $url = $this->absolute($url, (string) $response->header('Location'));
        }

        throw new RuntimeException('The page redirects too many times.');
    }

    /** One request, pinned to the address that was checked, so DNS cannot point it elsewhere afterwards. */
    private function fetch(string $url): \Illuminate\Http\Client\Response
    {
        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = (string) ($parts['host'] ?? '');
        if (! in_array($scheme, ['http', 'https'], true) || $host === '') {
            throw new RuntimeException('Only web links (http or https) can be opened.');
        }

        $port = (int) ($parts['port'] ?? ($scheme === 'https' ? 443 : 80));
        $ip = $this->publicAddressOf($host);

        try {
            return Http::timeout(8)->withUserAgent('Mozilla/5.0 (compatible; MusoftwareBot/1.0)')
                ->withOptions(['allow_redirects' => false, 'curl' => [CURLOPT_RESOLVE => ["{$host}:{$port}:{$ip}"]]])
                ->get($url);
        } catch (ConnectionException $e) {
            throw new RuntimeException('The site did not answer.', 0, $e);
        }
    }

    private function publicAddressOf(string $host): string
    {
        $addresses = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : (gethostbynamel($host) ?: []);
        $public = array_filter($addresses, fn (string $ip) => filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE));
        if ($addresses === [] || count($public) !== count($addresses)) {
            throw new RuntimeException('This address is not a public website.');
        }

        return $public[array_key_first($public)];
    }

    private function absolute(string $base, string $location): string
    {
        if ($location === '') {
            throw new RuntimeException('The page redirects nowhere.');
        }
        if (preg_match('#^https?://#i', $location)) {
            return $location;
        }

        $parts = parse_url($base);

        return $parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '').'/'.ltrim($location, '/');
    }

    /** @return array{title: string, description: string, text: string} */
    private function contentOf(string $html): array
    {
        $html = substr($html, 0, self::MAX_BYTES);
        preg_match('#<title[^>]*>(.*?)</title>#is', $html, $title);
        preg_match('#<meta[^>]+name=["\']description["\'][^>]+content=["\']([^"\']*)#i', $html, $description);
        $body = preg_replace('#<(script|style|noscript|svg)\b[^>]*>.*?</\1>#is', ' ', $html) ?? '';
        $text = trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($body), ENT_QUOTES | ENT_HTML5)));

        return [
            'title' => mb_substr(trim(html_entity_decode($title[1] ?? '')), 0, 300),
            'description' => mb_substr(trim(html_entity_decode($description[1] ?? '')), 0, 1000),
            'text' => mb_substr($text, 0, self::TEXT_CHARS),
        ];
    }
}
