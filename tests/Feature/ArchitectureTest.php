<?php

namespace Tests\Feature;

use App\Helpers\FinanceHelper;
use Tests\TestCase;

class ArchitectureTest extends TestCase
{
    public function test_financial_service_exists()
    {
        $this->assertTrue(class_exists(FinanceHelper::class));
    }
}
