<?php

declare(strict_types=1);

namespace App\Services\Privacy;

use App\Models\AdminAuditLog;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminDataAccessLogger
{
    private const SENSITIVE_VIEW_PATTERNS = [
        'admin/users*',
        'admin/kyc*',
        'admin/guest-tickets*',
        'admin/privacy-requests*',
    ];

    public static function observeResponse(Request $request, Response $response): void
    {
        if ($request->attributes->get('_admin_access_logged') === true) {
            return;
        }

        $actor = $request->user();
        if (! $actor) {
            return;
        }

        if (self::isExportResponse($request, $response)) {
            $request->attributes->set('_admin_access_logged', true);
            self::recordExport($request, $response, (int) $actor->id);

            return;
        }

        if ($request->isMethod('GET') && $request->is(...self::SENSITIVE_VIEW_PATTERNS) && $response->getStatusCode() < 400) {
            $request->attributes->set('_admin_access_logged', true);
            self::recordCustomerView($request, (int) $actor->id);
        }
    }

    private static function isExportResponse(Request $request, Response $response): bool
    {
        $disposition = strtolower((string) $response->headers->get('Content-Disposition', ''));
        if (str_contains($disposition, 'attachment')) {
            return true;
        }

        $contentType = strtolower((string) $response->headers->get('Content-Type', ''));
        foreach (['text/csv', 'application/csv', 'spreadsheetml', 'vnd.ms-excel'] as $exportMime) {
            if (str_contains($contentType, $exportMime)) {
                return true;
            }
        }

        $routeName = (string) ($request->route()?->getName() ?? '');

        return str_contains($routeName, 'export');
    }

    private static function recordExport(Request $request, Response $response, int $actorId): void
    {
        AdminAuditLog::create([
            'actor_user_id' => $actorId,
            'actor_ip' => $request->ip(),
            'actor_user_agent' => $request->userAgent(),
            'action' => 'admin.data.exported',
            'severity' => AdminAuditLog::SEVERITY_WARNING,
            'target_type' => $request->route()?->getName() ?: $request->path(),
            'target_id' => self::extractNumericTargetId($request),
            'meta' => [
                'path' => $request->path(),
                'route' => $request->route()?->getName(),
                'query' => $request->query(),
                'content_type' => $response->headers->get('Content-Type'),
                'content_disposition' => $response->headers->get('Content-Disposition'),
            ],
        ]);
    }

    private static function recordCustomerView(Request $request, int $actorId): void
    {
        AdminAuditLog::create([
            'actor_user_id' => $actorId,
            'actor_ip' => $request->ip(),
            'actor_user_agent' => $request->userAgent(),
            'action' => 'admin.customer_data.viewed',
            'severity' => AdminAuditLog::SEVERITY_INFO,
            'target_type' => $request->route()?->getName() ?: $request->path(),
            'target_id' => self::extractNumericTargetId($request),
            'meta' => [
                'path' => $request->path(),
                'route' => $request->route()?->getName(),
                'search' => $request->query('search'),
            ],
        ]);
    }

    private static function extractNumericTargetId(Request $request): ?int
    {
        foreach ($request->route()?->parameters() ?? [] as $param) {
            if (is_object($param) && isset($param->id) && is_numeric($param->id)) {
                return (int) $param->id;
            }
            if (is_numeric($param)) {
                return (int) $param;
            }
        }

        return null;
    }
}
