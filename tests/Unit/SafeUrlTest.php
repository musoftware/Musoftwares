<?php

namespace Tests\Unit;

use App\Support\SafeUrl;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SafeUrlTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function blockedUrls(): array
    {
        return [
            'loopback v4' => ['http://127.0.0.1/'],
            'loopback name' => ['http://localhost/admin'],
            'private 10/8' => ['http://10.0.0.5/'],
            'private 192.168' => ['https://192.168.1.1/'],
            'private 172.16' => ['http://172.16.3.4/'],
            'cloud metadata' => ['http://169.254.169.254/latest/meta-data/'],
            'zero network' => ['http://0.0.0.0/'],
            'carrier nat' => ['http://100.64.1.1/'],
            'loopback v6' => ['http://[::1]/'],
            'unique local v6' => ['http://[fd00::1]/'],
            'link local v6' => ['http://[fe80::1]/'],
            'mapped v4 loopback' => ['http://[::ffff:127.0.0.1]/'],
            'file scheme' => ['file:///etc/passwd'],
            'gopher scheme' => ['gopher://example.com/'],
            'ftp scheme' => ['ftp://example.com/'],
            'no host' => ['http:///path'],
            'unresolvable' => ['http://does-not-exist.invalid/'],
        ];
    }

    #[DataProvider('blockedUrls')]
    public function test_blocks_unsafe_urls(string $url): void
    {
        $this->assertNull(SafeUrl::inspect($url));
        $this->assertNull(SafeUrl::fetch($url));
    }

    public function test_allows_public_ip_literal_and_pins_it(): void
    {
        $target = SafeUrl::inspect('https://8.8.8.8/path');

        $this->assertNotNull($target);
        $this->assertSame('8.8.8.8', $target['ip']);
        $this->assertSame(443, $target['port']);
    }

    public function test_public_ip_check_covers_v4_and_v6(): void
    {
        $this->assertTrue(SafeUrl::isPublicIp('1.1.1.1'));
        $this->assertTrue(SafeUrl::isPublicIp('2606:4700:4700::1111'));
        $this->assertFalse(SafeUrl::isPublicIp('::ffff:10.0.0.1'));
        $this->assertFalse(SafeUrl::isPublicIp('not-an-ip'));
    }
}
