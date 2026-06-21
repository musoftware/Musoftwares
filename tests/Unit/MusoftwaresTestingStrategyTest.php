<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;

class MusoftwaresTestingStrategyTest extends TestCase
{
    /**
     * Test the basic environment setup as per Musoftwares Testing Strategy.
     * The strategy requires tests to be run in 'testing' environment 
     * using an in-memory SQLite database.
     */
    public function test_testing_strategy_environment_is_correct()
    {
        // 1. Assert we are in testing environment
        $this->assertEquals('testing', app()->environment(), 'App environment must be testing.');

        // 2. Assert database connection is SQLite
        $connection = DB::connection();
        $this->assertEquals('sqlite', $connection->getDriverName(), 'Database driver must be sqlite.');

        // 3. Assert database is in memory
        $this->assertEquals(':memory:', $connection->getDatabaseName(), 'Database must be in memory.');
    }
}
