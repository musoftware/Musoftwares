<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Helpers\FinanceHelper;

class ArchitectureTest extends TestCase
{
    public function test_financial_service_exists()
    {
        $this->assertTrue(class_exists(FinanceHelper::class));
    }
}
