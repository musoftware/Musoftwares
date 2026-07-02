<?php

use App\Http\Middleware\AccountantMiddleware;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\ClientMiddleware;
use App\Http\Middleware\EnforceFreelanceDomain;
use App\Http\Middleware\EnsureOnboardingCompleted;
use App\Http\Middleware\ForceJsonRequest;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\ModeratorMiddleware;
use App\Http\Middleware\RemoveSecurityHeaders;
use App\Http\Middleware\SecurityEnforcement;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\SubscriptionMiddleware;
use App\Http\Middleware\TenantMiddleware;
use App\Http\Middleware\VerifyEmbedKey;
use App\Http\Middleware\VerifySerialDeviceHmac;
use App\Models\BlockedIp;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Lang;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(SecurityEnforcement::class);
        $middleware->append(RemoveSecurityHeaders::class);
        $middleware->web(append: [
            'throttle:web',
            SetLocale::class,
            HandleInertiaRequests::class,
            EnforceFreelanceDomain::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
        $middleware->api(prepend: [
            'throttle:api',
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
            'admin' => AdminMiddleware::class,
            'moderator' => ModeratorMiddleware::class,
            'accountant' => AccountantMiddleware::class,
            'client' => ClientMiddleware::class,
            'tenant' => TenantMiddleware::class,
            'tenant.active' => TenantMiddleware::class,
            'subscription' => SubscriptionMiddleware::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'onboarding' => EnsureOnboardingCompleted::class,
            'force.json' => ForceJsonRequest::class,
            'embed' => VerifyEmbedKey::class,
            'serial.device.hmac' => VerifySerialDeviceHmac::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request) {
            $statusCode = $response->getStatusCode();

            // API or JSON requests: return generic error responses without exposing framework classes or path traces
            if ($request->expectsJson() || $request->is('api/*')) {
                if ($exception instanceof ValidationException) {
                    return $response;
                }

                $message = $response->headers->get('Content-Type') === 'application/json'
                    ? json_decode($response->getContent(), true)['message'] ?? $exception->getMessage()
                    : $exception->getMessage();

                if ($exception instanceof QueryException) {
                    $message = __('errors.database_error') === 'errors.database_error'
                        ? 'A database error occurred.'
                        : __('errors.database_error');
                }

                if ($exception instanceof ThrottleRequestsException) {
                    $ip = $request->ip();
                    $strikes = Cache::increment("throttle_strikes:{$ip}");
                    if ($strikes === 1) {
                        Cache::put("throttle_strikes:{$ip}", 1, 3600); // 1 hour
                    }
                    if ($strikes > 5) { // 5 rate limit hits in an hour -> block permanently
                        BlockedIp::firstOrCreate(
                            ['ip_address' => $ip],
                            ['reason' => 'Persistent rate limiting (Spam)', 'blocked_until' => null]
                        );
                        Cache::forget("blocked_ip:{$ip}"); // clear cache
                    }
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
            if (in_array($statusCode, [404, 403, 503, 429]) || ($statusCode === 500 && ! app()->environment(['local', 'testing']))) {
                if ($statusCode === 429 || $exception instanceof ThrottleRequestsException) {
                    $ip = $request->ip();
                    $strikes = Cache::increment("throttle_strikes:{$ip}");
                    if ($strikes === 1) {
                        Cache::put("throttle_strikes:{$ip}", 1, 3600); // 1 hour
                    }
                    if ($strikes > 5) {
                        BlockedIp::firstOrCreate(
                            ['ip_address' => $ip],
                            ['reason' => 'Persistent rate limiting (Spam)', 'blocked_until' => null]
                        );
                        Cache::forget("blocked_ip:{$ip}");
                    }
                }

                if (class_exists(Inertia::class)) {
                    $translationKey = match (true) {
                        $statusCode === 404 => 'errors.page_not_found',
                        $statusCode === 403 => 'errors.forbidden',
                        $statusCode === 429 => 'errors.too_many_requests',
                        $statusCode === 503 => 'errors.service_unavailable',
                        default => 'errors.something_went_wrong',
                    };

                    $userMessage = Lang::has($translationKey)
                        ? __($translationKey)
                        : ($statusCode === 404
                            ? 'The page you are looking for could not be found.'
                            : 'Something went wrong. Please try again.');

                    return Inertia::render('Error', [
                        'status' => $statusCode,
                        'message' => $userMessage,
                    ])
                        ->toResponse($request)
                        ->setStatusCode($statusCode)
                        ->withHeaders([
                            'X-Inertia-Flash-Error' => $userMessage,
                        ]);
                }
            } elseif ($statusCode === 419) {
                return back()->with([
                    'message' => __('errors.page_expired'),
                ]);
            } elseif ($statusCode >= 500 && ! app()->environment(['local', 'testing'])) {
                // Unhandled server error on a web request — surface it as a flash
                // so the admin sees the message in a toast instead of a silent redirect.
                $message = Lang::has('errors.something_went_wrong')
                    ? __('errors.something_went_wrong')
                    : 'Something went wrong. Please try again.';

                return back()->with([
                    'error' => $message,
                ]);
            }

            return $response;
        });
    })->create();
