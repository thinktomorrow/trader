<?php

namespace Thinktomorrow\Trader\Laravel;

use Purchase\Cart\Domain\Cart;
use Common\Notes\NoteCollection;
use Find\Catalog\Domain\Product;
use Illuminate\Events\Dispatcher;
use Purchase\Cart\Domain\CartItem;
use Purchase\Cart\Domain\DefaultCart;
use Illuminate\Support\ServiceProvider;
use Purchase\Cart\Domain\CartRepository;
use Purchase\Cart\Ports\DbCartRepository;
use Purchase\Cart\Domain\DefaultCartItem;
use Find\Catalog\Domain\ProductRepository;
use Purchase\Cart\Domain\CurrentCartSource;
use Common\Domain\References\ReferenceValue;
use Purchase\Cart\Domain\CartItemCollection;
use Purchase\Cart\Ports\DbCurrentCartSource;
use Purchase\Cart\Domain\CartReferenceSource;
use Common\Domain\References\DefaultReferenceValue;
use Find\Catalog\Ports\Laravel\DbCatalogRepository;
use Find\Catalog\Ports\Laravel\DbProductRepository;
use Purchase\Cart\Domain\DefaultCartItemCollection;
use Purchase\Items\Domain\PurchasableItemRepository;
use Purchase\Items\Ports\DbPurchasableItemRepository;
use Thinktomorrow\Trader\Common\Events\EventDispatcher;
use Purchase\Cart\Ports\LaravelCookieCartReferenceSource;
use Thinktomorrow\Trader\Integration\Basic\Find\DefaultProduct;
use Thinktomorrow\Trader\Purchase\Notes\Ports\DefaultNoteCollection;
use Thinktomorrow\Trader\Integration\Basic\Find\Catalog\Reads\ProductRead;
use Thinktomorrow\Trader\Integration\Basic\Find\Catalog\Reads\CatalogRepository;
use Thinktomorrow\Trader\Integration\Basic\Find\Catalog\Reads\DefaultProductRead;

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

        /*
         * --------------------------------------------------
         * FIND
         * --------------------------------------------------
         */
        $this->app->bind(Product::class, DefaultProduct::class);
        $this->app->bind(ProductRead::class, DefaultProductRead::class);
        $this->app->bind(CatalogRepository::class, DbCatalogRepository::class);
        $this->app->bind(ProductRepository::class, DbProductRepository::class);

        /*
         * --------------------------------------------------
         * PURCHASE
         * --------------------------------------------------
         */
        $this->app->bind(NoteCollection::class, DefaultNoteCollection::class);
        $this->app->bind(Cart::class, DefaultCart::class);
        $this->app->bind(CartItem::class, DefaultCartItem::class);
        $this->app->bind(CartItemCollection::class, DefaultCartItemCollection::class);
        $this->app->bind(CartRepository::class, DbCartRepository::class);
        $this->app->bind(ReferenceValue::class, DefaultReferenceValue::class);
        $this->app->bind(CartReferenceSource::class, LaravelCookieCartReferenceSource::class);
        $this->app->singleton(CurrentCartSource::class, DbCurrentCartSource::class);
        $this->app->bind(PurchasableItemRepository::class, DbPurchasableItemRepository::class);
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
