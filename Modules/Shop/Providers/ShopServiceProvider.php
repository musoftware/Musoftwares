<?php

namespace Modules\Shop\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Modules\Shop\Models\ShopCategory;
use Modules\Shop\Services\Cart;

class ShopServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(module_path('Shop', 'config/shop.php'), 'shop');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(module_path('Shop', 'Database/Migrations'));
        $this->loadViewsFrom(module_path('Shop', 'Resources/views'), 'shop');
        $this->loadRoutesFrom(module_path('Shop', 'routes/web.php'));

        View::composer('shop::layout', function ($view): void {
            $view->with([
                'navCategories' => ShopCategory::visible()->get(['id', 'name', 'slug']),
                'cartCount' => app(Cart::class)->count(),
            ]);
        });
    }
}
