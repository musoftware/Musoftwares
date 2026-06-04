<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(\App\Http\Middleware\RemoveSecurityHeaders::class);
        $middleware->web(append: [
            \Modules\ERP\Http\Middleware\ShareTeamMemberSession::class,
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'financial/add-balance/webhook',
            'freelance/point-purchases/webhook',
            'subscriptions/kashier/webhook',
            'api/serial/device',   // Serial license check-in — called by client software, no browser session
            'crm/whatsapp/webhook/*', // WhatsApp provider webhooks — external POST requests
            'sms-pay/*/verify', // Hosted checkout embedded via iframe across domains
        ]);


        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'moderator' => \App\Http\Middleware\ModeratorMiddleware::class,
            'client' => \App\Http\Middleware\ClientMiddleware::class,
            'tenant' => \App\Http\Middleware\TenantMiddleware::class,
            'tenant.active' => \App\Http\Middleware\TenantMiddleware::class,
            'subscription' => \App\Http\Middleware\SubscriptionMiddleware::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'onboarding' => \App\Http\Middleware\EnsureOnboardingCompleted::class,
            'erp.team.permissions' => \Modules\ERP\Http\Middleware\EnforceTeamMemberPermissions::class,
            'reseller.sharing' => \App\Http\Middleware\ResellerSharingGuard::class,
            'force.json' => \App\Http\Middleware\ForceJsonRequest::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->respond(function (\Symfony\Component\HttpFoundation\Response $response, \Throwable $exception, \Illuminate\Http\Request $request) {
            $statusCode = $response->getStatusCode();

            // API or JSON requests: return generic error responses without exposing framework classes or path traces
            if ($request->expectsJson() || $request->is('api/*')) {
                if ($exception instanceof \Illuminate\Validation\ValidationException) {
                    return $response;
                }

                $message = $response->headers->get('Content-Type') === 'application/json'
                    ? json_decode($response->getContent(), true)['message'] ?? $exception->getMessage()
                    : $exception->getMessage();

                if ($exception instanceof \Illuminate\Database\QueryException) {
                    $message = __('errors.database_error') === 'errors.database_error'
                        ? 'A database error occurred.'
                        : __('errors.database_error');
                }

                return response()->json([
                    'status' => 'error',
                    'message' => $message ?: 'An error occurred.',
                ], in_array($statusCode, [200, 0]) ? 500 : $statusCode);
            }

            // If we already rendered a custom ERP error component, preserve it
            $content = $response->getContent();
            if (is_string($content) && (str_contains($content, 'ERP/Errors/NotFound') || str_contains($content, 'ERP\\/Errors\\/NotFound'))) {
                return $response;
            }

            // Web requests - render custom Inertia error page for 404/403/503
            if (in_array($statusCode, [404, 403, 503]) || ($statusCode === 500 && ! app()->environment(['local', 'testing']))) {
                if (class_exists(\Inertia\Inertia::class)) {
                    return \Inertia\Inertia::render('Error', ['status' => $statusCode])
                        ->toResponse($request)
                        ->setStatusCode($statusCode);
                }
            } elseif ($statusCode === 419) {
                return back()->with([
                    'message' => 'The page expired, please try again.',
                ]);
            }

            return $response;
        });
    })->create();
