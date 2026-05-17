<?php

namespace Tests;

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
    }
}
