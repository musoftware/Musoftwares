<?php

namespace Tests\Feature;

use Tests\TestCase;

/** A security scan found these headers missing on the live site; every page must carry them. */
class SecurityHeadersTest extends TestCase
{
    public function test_pages_carry_the_browser_security_headers(): void
    {
        $response = $this->get('/login');

        $response->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(self), geolocation=()')
            ->assertHeaderMissing('X-Powered-By')
            ->assertHeaderMissing('Strict-Transport-Security');
    }

    public function test_https_pages_tell_browsers_to_stay_on_https(): void
    {
        $this->get('https://localhost/login')->assertHeader('Strict-Transport-Security', 'max-age=31536000');
    }

    public function test_api_answers_carry_them_too(): void
    {
        $this->postJson('/api/client-systems/check-in', [])->assertUnauthorized()->assertHeader('X-Content-Type-Options', 'nosniff');
    }
}
