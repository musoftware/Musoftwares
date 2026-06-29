<?php

namespace App\Services;

use App\Models\AdminAuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Writes structured actor attribution for sensitive admin actions.
 *
 * The goal is to leave a tamper-evident (append-only) trail of who did what
 * to which resource, including impersonation chains, password resets, broadcast
 * sends, settings writes, and unauthenticated/automated webhook actions.
 *
 * Failures to write MUST NOT silently drop: if the DB write throws we surface
 * the failure to the application log so a downstream monitoring/alerting hook
 * can page on it.
 */
class AdminAuditService extends BaseService
{
    public function record(
        string $action,
        ?Model $target = null,
        array $meta = [],
        string $severity = AdminAuditLog::SEVERITY_INFO,
        ?int $actorUserId = null,
        ?Request $request = null
    ): AdminAuditLog {
        $request ??= request();

        $actorUserId ??= Auth::id();

        try {
            return AdminAuditLog::create([
                'actor_user_id' => $actorUserId,
                'actor_ip' => $request?->ip(),
                'actor_user_agent' => $request ? mb_substr((string) $request->userAgent(), 0, 512) : null,
                'action' => $action,
                'severity' => $severity,
                'target_type' => $target ? $this->mapMorphClass($target) : null,
                'target_id' => $target ? (string) $target->getKey() : null,
                'meta' => $this->sanitizeMeta($meta),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Never break the calling action because audit failed, but make the
            // failure loud so it can be alerted on.
            Log::error('AdminAuditService::record failed', [
                'action' => $action,
                'actor' => $actorUserId,
                'error' => $e->getMessage(),
            ]);

            return new AdminAuditLog(['action' => $action, 'severity' => $severity]);
        }
    }

    /**
     * For non-Eloquent targets (string IDs, external IDs, polymorphic references
     * we don't want to instantiate). target_type is a free-form short string
     * (e.g. 'kashier_webhook', 'serial_device', 'broadcast').
     */
    public function recordRaw(
        string $action,
        ?string $targetType,
        ?string $targetId,
        array $meta = [],
        string $severity = AdminAuditLog::SEVERITY_INFO,
        ?int $actorUserId = null,
        ?Request $request = null
    ): AdminAuditLog {
        $request ??= request();

        $actorUserId ??= Auth::id();

        try {
            return AdminAuditLog::create([
                'actor_user_id' => $actorUserId,
                'actor_ip' => $request?->ip(),
                'actor_user_agent' => $request ? mb_substr((string) $request->userAgent(), 0, 512) : null,
                'action' => $action,
                'severity' => $severity,
                'target_type' => $targetType,
                'target_id' => $targetId,
                'meta' => $this->sanitizeMeta($meta),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('AdminAuditService::recordRaw failed', [
                'action' => $action,
                'actor' => $actorUserId,
                'target_type' => $targetType,
                'target_id' => $targetId,
                'error' => $e->getMessage(),
            ]);

            return new AdminAuditLog(['action' => $action, 'severity' => $severity]);
        }
    }

    public function countForActorSince(string $action, int $actorUserId, \DateTimeInterface $since): int
    {
        return AdminAuditLog::where('action', $action)
            ->where('actor_user_id', $actorUserId)
            ->where('created_at', '>=', $since)
            ->count();
    }

    private function mapMorphClass(Model $model): string
    {
        $class = $model::class;

        // Strip the App\Models\ and Modules\*\Models\ prefixes to keep the column readable.
        return preg_replace('/^App\\\\Models\\\\/', '', $class)
            ?? $class;
    }

    private function sanitizeMeta(array $meta): array
    {
        // Redact well-known sensitive keys. This is best-effort; callers should
        // also avoid putting full request payloads into meta.
        $blocked = ['password', 'password_confirmation', 'current_password', 'token', 'secret', 'api_key', 'authorization'];

        array_walk_recursive($meta, function (&$value, $key) use ($blocked) {
            if (is_string($key) && in_array(strtolower($key), $blocked, true)) {
                $value = '[redacted]';
            }
        });

        return $meta;
    }
}
