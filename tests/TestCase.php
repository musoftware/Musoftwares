<?php

namespace Tests;

use App\Models\AdminAuditLog;
use App\Models\CurrenciesExchange;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Ensure all module migrations are run if needed.
        // We'll mock console output to bypass the Module Migration prompt in Testing
        $this->withoutMockingConsoleOutput();
        Artisan::call('module:migrate', ['--force' => true, '--no-interaction' => true]);

        // Reset virtual audit logs between tests
        AdminAuditLog::$logs = [];

        // The rate cache is static, so a rate (or a missing pair) from one test must not leak into the next.
        CurrenciesExchange::flushCache();
    }

    /**
     * Intercept and assert against virtual audit logs rather than the database.
     */
    public function assertDatabaseHas($table, array $data = [], $connection = null)
    {
        if ($table === 'admin_audit_logs') {
            $found = false;
            foreach (AdminAuditLog::$logs as $log) {
                $match = true;
                foreach ($data as $key => $value) {
                    if (($log->{$key} ?? null) != $value) {
                        $match = false;
                        break;
                    }
                }
                if ($match) {
                    $found = true;
                    break;
                }
            }
            $this->assertTrue($found, 'Failed asserting that virtual admin_audit_logs has matching record: '.json_encode($data));

            return $this;
        }

        return parent::assertDatabaseHas($table, $data, $connection);
    }

    /** The whole sitemap: /sitemap.xml is an index, so fetch every part it lists and join them. */
    protected function sitemapXml(): string
    {
        $index = $this->get('/sitemap.xml')->assertOk()->getContent();
        preg_match_all('#<loc>([^<]+)</loc>#', $index, $matches);

        return collect($matches[1])
            ->map(fn (string $url) => $this->get(parse_url($url, PHP_URL_PATH))->assertOk()->getContent())
            ->implode("
");
    }
}
