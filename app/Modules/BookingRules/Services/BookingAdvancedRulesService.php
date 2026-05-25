<?php

namespace App\Modules\BookingRules\Services;

use App\Modules\BookingRules\Models\BookingAdvancedRule;
use App\Modules\BookingRules\Models\BookingAdvancedRuleExecution;

class BookingAdvancedRulesService
{
    public function getActiveRulesForEvent(int $tenantId, string $eventTrigger)
    {
        return BookingAdvancedRule::where('tenant_id', $tenantId)
            ->where('event_trigger', $eventTrigger)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('valid_from')->orWhere('valid_from', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('valid_until')->orWhere('valid_until', '>=', now());
            })
            ->orderBy('priority', 'desc')
            ->with(['conditions', 'actions'])
            ->get();
    }

    public function createExecutionRecord(int $tenantId, int $ruleId, ?int $bookingId, bool $isDryRun = false): BookingAdvancedRuleExecution
    {
        return BookingAdvancedRuleExecution::create([
            'tenant_id' => $tenantId,
            'rule_id' => $ruleId,
            'booking_id' => $bookingId,
            'status' => 'pending',
            'is_dry_run' => $isDryRun,
        ]);
    }
}
