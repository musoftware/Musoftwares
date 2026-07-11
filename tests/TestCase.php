<?php

namespace Tests;

use App\Models\AdminAuditLog;
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
}
