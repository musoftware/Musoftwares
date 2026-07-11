<?php

namespace App\Services;

use App\Models\AdminAuditLog;
use App\Models\CostTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CostTransactionAuditService extends BaseService
{
    public const ACTION_CREATED = 'costs.created';

    public const ACTION_UPDATED = 'costs.updated';

    public const ACTION_DELETED = 'costs.deleted';

    public function log(
        string $action,
        int $costId,
        array $meta = [],
        string $severity = AdminAuditLog::SEVERITY_INFO,
        ?Request $request = null,
    ): AdminAuditLog {
        $request ??= request();

        return AdminAuditLog::create([
            'actor_user_id' => Auth::id(),
            'actor_ip' => $request?->ip(),
            'actor_user_agent' => $request?->userAgent(),
            'action' => $action,
            'severity' => $severity,
            'target_type' => CostTransaction::class,
            'target_id' => $costId,
            'meta' => array_filter([
                'amount' => $meta['amount'] ?? null,
                'currency_id' => $meta['currency_id'] ?? null,
                'reason' => $meta['reason'] ?? null,
                'user_id' => $meta['user_id'] ?? null,
                'project_id' => $meta['project_id'] ?? null,
                'changed' => $meta['changed'] ?? null,
                'before' => $meta['before'] ?? null,
                'after' => $meta['after'] ?? null,
            ], static fn ($v) => $v !== null),
        ]);
    }
}
