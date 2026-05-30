<?php

namespace Modules\Booking\app\Features\BookingRules\Services;

use Modules\Booking\app\Features\BookingRules\Models\BookingAdvancedRuleLog;

class BookingRulesAuditService
{
    public function logInfo(int $executionId, string $message, array $context = []): void
    {
        $this->log($executionId, 'info', $message, $context);
    }

    public function logWarning(int $executionId, string $message, array $context = []): void
    {
        $this->log($executionId, 'warning', $message, $context);
    }

    public function logError(int $executionId, string $message, array $context = []): void
    {
        $this->log($executionId, 'error', $message, $context);
    }

    protected function log(int $executionId, string $level, string $message, array $context): void
    {
        BookingAdvancedRuleLog::create([
            // In a real scenario we'd fetch tenant_id from execution
            'tenant_id' => 1, 
            'execution_id' => $executionId,
            'level' => $level,
            'message' => $message,
            'context' => $context,
        ]);
    }
}
