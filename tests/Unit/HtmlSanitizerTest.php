<?php

namespace Tests\Unit;

use App\Support\HtmlSanitizer;
use PHPUnit\Framework\TestCase;

class HtmlSanitizerTest extends TestCase
{
    public function test_strips_scripts_event_handlers_and_unsafe_urls(): void
    {
        $dirty = '<p onclick="x()">Hi<script>alert(1)</script></p>'
            .'<a href="java&#x09;script:alert(1)">bad</a>'
            .'<img src="x" onerror="alert(1)">'
            .'<iframe src="https://evil.test"></iframe>'
            .'<svg><script>alert(2)</script></svg>';

        $clean = HtmlSanitizer::clean($dirty);

        $this->assertStringNotContainsString('script', $clean);
        $this->assertStringNotContainsString('onclick', $clean);
        $this->assertStringNotContainsString('onerror', $clean);
        $this->assertStringNotContainsString('iframe', $clean);
        $this->assertStringContainsString('<p>Hi</p>', $clean);
        $this->assertStringContainsString('<a>bad</a>', $clean);
    }

    public function test_keeps_safe_formatting_links_and_unicode(): void
    {
        $clean = HtmlSanitizer::clean('<h2>عنوان</h2><a href="https://example.com" target="x">ok</a><custom>text</custom>');

        $this->assertStringContainsString('<h2>عنوان</h2>', $clean);
        $this->assertStringContainsString('href="https://example.com"', $clean);
        $this->assertStringContainsString('rel="noopener noreferrer nofollow"', $clean);
        $this->assertStringContainsString('text', $clean);
        $this->assertStringNotContainsString('<custom>', $clean);
        $this->assertSame('', HtmlSanitizer::clean(null));
    }
}
