<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Guard for server-side requests to user-supplied URLs (SSRF protection).
 *
 * Only http/https URLs whose host resolves to public IP addresses are allowed.
 * Requests pin the checked IP (CURLOPT_RESOLVE) so DNS cannot change between
 * the check and the connect, and every redirect hop is checked again.
 */
final class SafeUrl
{
    private const ALLOWED_SCHEMES = ['http' => 80, 'https' => 443];

    private const MAX_REDIRECTS = 3;

    /**
     * Extra blocked ranges not covered by PHP's private/reserved filter flags.
     */
    private const BLOCKED_CIDRS = [
        '0.0.0.0/8',
        '100.64.0.0/10',
        '127.0.0.0/8',
        '169.254.0.0/16',
        '192.0.0.0/24',
        '198.18.0.0/15',
        '::/128',
        '::1/128',
        '64:ff9b::/96',
        'fc00::/7',
        'fe80::/10',
    ];

    /**
     * Check a URL. Returns host, port and the pinned public IP, or null if unsafe.
     *
     * @return array{url: string, host: string, port: int, ip: string}|null
     */
    public static function inspect(string $url): ?array
    {
        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower(trim((string) ($parts['host'] ?? ''), '[]'));

        if (! isset(self::ALLOWED_SCHEMES[$scheme]) || $host === '') {
            return null;
        }

        $ips = self::resolve($host);
        if ($ips === [] || array_filter($ips, fn (string $ip) => ! self::isPublicIp($ip)) !== []) {
            return null;
        }

        return [
            'url' => $url,
            'host' => $host,
            'port' => (int) ($parts['port'] ?? self::ALLOWED_SCHEMES[$scheme]),
            'ip' => $ips[0],
        ];
    }

    /**
     * Whether an IP address is publicly routable (IPv4 or IPv6).
     */
    public static function isPublicIp(string $ip): bool
    {
        $flags = FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;
        if (filter_var($ip, FILTER_VALIDATE_IP, $flags) === false) {
            return false;
        }

        $mapped = self::unwrapMappedIpv4($ip);
        if ($mapped !== null) {
            return self::isPublicIp($mapped);
        }

        foreach (self::BLOCKED_CIDRS as $cidr) {
            if (self::inCidr($ip, $cidr)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Run a cURL request with SSRF protection. Redirects are followed manually and re-checked.
     *
     * @param  array<int, mixed>  $options  Extra cURL options (FOLLOWLOCATION and RESOLVE are overridden).
     * @return array{body: string|false, info: array<string, mixed>}|null Null when the URL or a redirect is unsafe.
     */
    public static function fetch(string $url, array $options = [], bool $followRedirects = true): ?array
    {
        $current = $url;

        for ($hop = 0; $hop <= self::MAX_REDIRECTS; $hop++) {
            $target = self::inspect($current);
            if ($target === null) {
                return null;
            }

            $result = self::request($target, $options);
            $next = (string) ($result['info']['redirect_url'] ?? '');

            if (! $followRedirects || $next === '') {
                return $result;
            }

            $current = $next;
        }

        return null;
    }

    /**
     * @param  array{url: string, host: string, port: int, ip: string}  $target
     * @param  array<int, mixed>  $options
     * @return array{body: string|false, info: array<string, mixed>}
     */
    private static function request(array $target, array $options): array
    {
        $ch = curl_init();
        curl_setopt_array($ch, $options + [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 6]);
        curl_setopt_array($ch, [
            CURLOPT_URL => $target['url'],
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_RESOLVE => self::pinEntry($target),
        ]);

        $body = curl_exec($ch);
        $info = curl_getinfo($ch);

        return ['body' => $body, 'info' => is_array($info) ? $info : []];
    }

    /**
     * CURLOPT_RESOLVE entry that pins the checked IP. IP-literal hosts need no pin.
     *
     * @param  array{url: string, host: string, port: int, ip: string}  $target
     * @return list<string>
     */
    private static function pinEntry(array $target): array
    {
        if (filter_var($target['host'], FILTER_VALIDATE_IP) !== false) {
            return [];
        }

        $ip = str_contains($target['ip'], ':') ? '['.$target['ip'].']' : $target['ip'];

        return [$target['host'].':'.$target['port'].':'.$ip];
    }

    /**
     * @return list<string>
     */
    private static function resolve(string $host): array
    {
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return [$host];
        }

        $ipv4 = gethostbynamel($host) ?: [];
        $ipv6 = checkdnsrr($host, 'AAAA') ? array_column(dns_get_record($host, DNS_AAAA) ?: [], 'ipv6') : [];

        return array_values(array_unique([...$ipv4, ...$ipv6]));
    }

    private static function unwrapMappedIpv4(string $ip): ?string
    {
        if (! str_contains($ip, ':')) {
            return null;
        }

        $packed = inet_pton($ip);
        $prefix = str_repeat("\0", 10)."\xff\xff";
        if ($packed === false || strlen($packed) !== 16 || ! str_starts_with($packed, $prefix)) {
            return null;
        }

        return (string) inet_ntop(substr($packed, 12));
    }

    private static function inCidr(string $ip, string $cidr): bool
    {
        [$subnet, $bits] = explode('/', $cidr);
        $ipBin = inet_pton($ip);
        $subnetBin = inet_pton($subnet);

        if ($ipBin === false || $subnetBin === false || strlen($ipBin) !== strlen($subnetBin)) {
            return false;
        }

        $bytes = intdiv((int) $bits, 8);
        $remainder = (int) $bits % 8;
        if (substr($ipBin, 0, $bytes) !== substr($subnetBin, 0, $bytes)) {
            return false;
        }

        if ($remainder === 0) {
            return true;
        }

        $mask = 0xFF << (8 - $remainder) & 0xFF;

        return (ord($ipBin[$bytes]) & $mask) === (ord($subnetBin[$bytes]) & $mask);
    }
}
