<?php

namespace Thinktomorrow\Trader\Laravel;

use Illuminate\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;
use Thinktomorrow\Trader\Find\Catalog\Reads\Product;
use Thinktomorrow\Trader\Common\Events\EventDispatcher;
use Thinktomorrow\Trader\Find\Catalog\Reads\DefaultProduct;
use Thinktomorrow\Trader\Purchase\Cart\Domain\CartRepository;
use Thinktomorrow\Trader\Find\Catalog\Reads\CatalogRepository;
use Thinktomorrow\Trader\Find\Catalog\Reads\ProductRepository;
use Thinktomorrow\Trader\Purchase\Cart\Ports\DbCartRepository;
use Thinktomorrow\Trader\Find\Catalog\Ports\Laravel\DbCatalogRepository;
use Thinktomorrow\Trader\Find\Catalog\Ports\Laravel\DbProductRepository;
use Thinktomorrow\Trader\Purchase\Cart\Domain\CurrentCartSource;
use Thinktomorrow\Trader\Purchase\Cart\Ports\DbCurrentCartSource;
use Thinktomorrow\Trader\Common\Domain\References\ReferenceValue;
use Thinktomorrow\Trader\Purchase\Cart\Domain\CartReferenceSource;
use Thinktomorrow\Trader\Common\Domain\References\DefaultReferenceValue;
use Thinktomorrow\Trader\Purchase\Cart\Ports\LaravelCookieCartReferenceSource;

class TraderServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(EventDispatcher::class, Dispatcher::class);

        $this->app->bind(Product::class, DefaultProduct::class);
        $this->app->bind(CatalogRepository::class, DbCatalogRepository::class);
        $this->app->bind(ProductRepository::class, DbProductRepository::class);

        $this->app->bind(CartRepository::class, DbCartRepository::class);
        $this->app->bind(ReferenceValue::class, DefaultReferenceValue::class);
        $this->app->bind(CartReferenceSource::class, LaravelCookieCartReferenceSource::class);
        $this->app->singleton(CurrentCartSource::class, DbCurrentCartSource::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
