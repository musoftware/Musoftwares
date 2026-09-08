<?php

namespace Tests\Unit;

use App\Models\User;
use Tests\TestCase;

class UserTimerReportTest extends TestCase
{
    public function test_timer_report_query_builds_without_errors(): void
    {
        $user = new User();
        $relation = $user->timer_report();

        $this->assertNotNull($relation);
        $sql = $relation->toSql();
        $this->assertStringContainsString('DATE(date_start)', $sql);
        $this->assertStringContainsString('sum_seconds', $sql);
    }
}
