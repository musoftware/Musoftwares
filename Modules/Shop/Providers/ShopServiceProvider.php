<?php

namespace Modules\Shop\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Modules\Shop\Console\ArrangeProductImages;
use Modules\Shop\Console\ImportAngaznyDriveImages;
use Modules\Shop\Console\RewriteProductContent;
use Modules\Shop\Console\SendOrdersToAngazny;
use Modules\Shop\Console\SyncAngaznyCatalogue;
use Modules\Shop\Models\ShopCategory;
use Modules\Shop\Services\Cart;
use Modules\Shop\Services\Content\ClaudeProductCopyWriter;
use Modules\Shop\Services\Content\ClaudeProductImageMatcher;
use Modules\Shop\Services\Content\ProductCopyWriter;
use Modules\Shop\Services\Content\ProductImageMatcher;

class ShopServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(module_path('Shop', 'config/shop.php'), 'shop');
        $this->commands([SyncAngaznyCatalogue::class, SendOrdersToAngazny::class, ImportAngaznyDriveImages::class, RewriteProductContent::class, ArrangeProductImages::class]);
        $this->app->bind(ProductCopyWriter::class, ClaudeProductCopyWriter::class);
        $this->app->bind(ProductImageMatcher::class, ClaudeProductImageMatcher::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(module_path('Shop', 'Database/Migrations'));
        $this->loadViewsFrom(module_path('Shop', 'Resources/views'), 'shop');
        $this->loadRoutesFrom(module_path('Shop', 'routes/web.php'));

        View::composer('shop::layout', function ($view): void {
            $view->with([
                'navCategories' => ShopCategory::visible()->withVisibleProducts()->get(['id', 'name', 'slug']),
                'cartCount' => app(Cart::class)->count(),
            ]);
        });
    }
}
