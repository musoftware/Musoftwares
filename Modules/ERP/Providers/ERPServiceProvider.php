<?php

namespace Modules\ERP\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Modules\ERP\Console\ProcessRecurringEntries;
use Modules\ERP\Services\RecurringService;
use Modules\ERP\Listeners\SyncBookingClientToErpListener;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ERPServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(
            module_path('ERP', 'Database/Migrations')
        );

        $this->loadViewsFrom(module_path('ERP', 'resources/views'), 'erp');

        if ($this->app->runningInConsole()) {
            $this->commands([
                ProcessRecurringEntries::class,
            ]);
        }

        // ── Cross-Module Event Listeners ──────────────────────────────────
        // The ERP module listens for Booking events only if Booking is loaded.
        // This keeps both modules independently deployable SaaS products.
        if (class_exists(\Modules\Booking\Events\BookingConfirmed::class)) {
            Event::listen(
                \Modules\Booking\Events\BookingConfirmed::class,
                SyncBookingClientToErpListener::class
            );
        }

        // Accounting Events
        Event::listen(\App\Events\InvoicePaid::class, [\Modules\ERP\Listeners\AccountingListener::class, 'handle']);
        Event::listen(\App\Events\WalletCredited::class, [\Modules\ERP\Listeners\AccountingListener::class, 'handle']);
        Event::listen(\App\Events\WalletDebited::class, [\Modules\ERP\Listeners\AccountingListener::class, 'handle']);

        $this->registerExceptionRenderers();
    }

    protected function registerExceptionRenderers(): void
    {
        $handler = $this->app->make(ExceptionHandler::class);

        $handleModelNotFound = function ($model, \Illuminate\Http\Request $request) {
            $mapping = [
                \Modules\ERP\Models\Project::class => [
                    'section' => 'projects',
                    'message' => __('erp.project_not_found'),
                ],
                \Modules\ERP\Models\TenantClient::class => [
                    'section' => 'clients',
                    'message' => __('erp.client_not_found'),
                ],
                \Modules\ERP\Models\Invoice::class => [
                    'section' => 'invoices',
                    'message' => __('erp.invoice_not_found'),
                ],
                \Modules\ERP\Models\ERPTask::class => [
                    'section' => 'tasks',
                    'message' => __('erp.task_not_found'),
                ],
                \Modules\ERP\Models\SupportTicket::class => [
                    'section' => 'tickets',
                    'message' => __('erp.ticket_not_found'),
                ],
                \Modules\ERP\Models\Expense::class => [
                    'section' => 'expenses',
                    'message' => __('erp.expense_not_found'),
                ],
                \Modules\ERP\Models\InvoiceCost::class => [
                    'section' => 'expenses',
                    'message' => __('erp.expense_not_found'),
                ],
            ];

            if (array_key_exists($model, $mapping)) {
                $section = $mapping[$model]['section'];
                $message = $mapping[$model]['message'];

                if ($request->expectsJson() || $request->is('api/*')) {
                    return response()->json([
                        'status' => 'error',
                        'message' => $message,
                    ], 404);
                }

                return \Inertia\Inertia::render('ERP/Errors/NotFound', [
                    'message' => $message,
                    'section' => $section,
                ])->toResponse($request)->setStatusCode(404);
            }

            return null;
        };

        $handler->renderable(function (ModelNotFoundException $e, \Illuminate\Http\Request $request) use ($handleModelNotFound) {
            return $handleModelNotFound($e->getModel(), $request);
        });

        $handler->renderable(function (NotFoundHttpException $e, \Illuminate\Http\Request $request) use ($handleModelNotFound) {
            $previous = $e->getPrevious();
            if ($previous instanceof ModelNotFoundException) {
                return $handleModelNotFound($previous->getModel(), $request);
            }
            return null;
        });
    }
}
