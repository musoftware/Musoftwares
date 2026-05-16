<?php

namespace Tests\Feature;

use Tests\TestCase;
use Modules\Core\Services\FinancialTransactionService;

class ArchitectureTest extends TestCase
{
    public function test_financial_service_exists()
    {
        $this->assertTrue(class_exists(FinancialTransactionService::class));
    }
}
