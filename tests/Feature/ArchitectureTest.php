<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\FinancialTransactionService;

class ArchitectureTest extends TestCase
{
    public function test_financial_service_exists()
    {
        $this->assertTrue(class_exists(FinancialTransactionService::class));
    }
}
