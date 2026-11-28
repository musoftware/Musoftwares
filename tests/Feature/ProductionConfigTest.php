<?php

namespace Tests\Feature;

use Tests\TestCase;

class ProductionConfigTest extends TestCase
{
    /**
     * Test that the production environment file has the correct Redis configurations.
     */
    public function test_production_env_file_has_redis_configured(): void
    {
        $path = base_path('.env.production');

        // the file holds production secrets and is never committed, so CI and fresh clones have nothing to check
        if (! file_exists($path)) {
            $this->markTestSkipped('.env.production is not on this machine.');
        }

        $content = file_get_contents($path);

        // Assert critical drivers are correctly set
        $this->assertStringContainsString('QUEUE_CONNECTION=database', $content);
        $this->assertStringContainsString('SESSION_DRIVER=redis', $content);

        // Verify Mail configuration
        $this->assertStringContainsString('MAIL_MAILER=mailgun', $content);
        $this->assertStringContainsString('MAILGUN_DOMAIN=', $content);
        $this->assertStringContainsString('CACHE_STORE=redis', $content);
        $this->assertStringContainsString('REDIS_CLIENT=phpredis', $content);
    }
}
