<?php

namespace Modules\WhatsappSender\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\WhatsappSender\Services\MetaWhatsappService;

class WhatsappSenderServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MetaWhatsappService::class, function ($app) {
            return new MetaWhatsappService();
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(module_path('WhatsappSender', 'Database/Migrations'));
        $this->loadTranslationsFrom(module_path('WhatsappSender', 'lang'), 'whatsapp-sender');

        Route::middleware('web')
            ->group(module_path('WhatsappSender', 'routes/web.php'));

        Route::middleware('api')
            ->prefix('api/v1')
            ->group(module_path('WhatsappSender', 'routes/api.php'));
    }
}
