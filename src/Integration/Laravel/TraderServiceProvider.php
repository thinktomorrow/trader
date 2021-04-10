<?php

namespace Thinktomorrow\Trader\Laravel;

use Illuminate\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;
use Thinktomorrow\Trader\Purchase\Cart\Domain\Cart;
use Thinktomorrow\Trader\Find\Catalog\Domain\Product;
use Thinktomorrow\Trader\Common\Events\EventDispatcher;
use Thinktomorrow\Trader\Purchase\Cart\Domain\CartItem;
use Thinktomorrow\Trader\Integration\Basic\Find\Catalog\Reads\ProductRead;
use Thinktomorrow\Trader\Purchase\Cart\Domain\DefaultCart;
use Thinktomorrow\Trader\Integration\Basic\Find\DefaultProduct;
use Thinktomorrow\Trader\Purchase\Cart\Domain\CartRepository;
use Thinktomorrow\Trader\Integration\Basic\Find\Catalog\Reads\CatalogRepository;
use Thinktomorrow\Trader\Purchase\Cart\Ports\DbCartRepository;
use Thinktomorrow\Trader\Common\Notes\NoteCollection;
use Thinktomorrow\Trader\Purchase\Cart\Domain\DefaultCartItem;
use Thinktomorrow\Trader\Find\Catalog\Domain\ProductRepository;
use Thinktomorrow\Trader\Integration\Basic\Find\Catalog\Reads\DefaultProductRead;
use Thinktomorrow\Trader\Purchase\Cart\Domain\CartItemCollection;
use Thinktomorrow\Trader\Purchase\Notes\Ports\DefaultNoteCollection;
use Thinktomorrow\Trader\Find\Catalog\Ports\Laravel\DbCatalogRepository;
use Thinktomorrow\Trader\Find\Catalog\Ports\Laravel\DbProductRepository;
use Thinktomorrow\Trader\Purchase\Cart\Domain\CurrentCartSource;
use Thinktomorrow\Trader\Purchase\Cart\Ports\DbCurrentCartSource;
use Thinktomorrow\Trader\Common\Domain\References\ReferenceValue;
use Thinktomorrow\Trader\Purchase\Cart\Domain\CartReferenceSource;
use Thinktomorrow\Trader\Common\Domain\References\DefaultReferenceValue;
use Thinktomorrow\Trader\Purchase\Cart\Domain\DefaultCartItemCollection;
use Thinktomorrow\Trader\Purchase\Items\Domain\PurchasableItemRepository;
use Thinktomorrow\Trader\Purchase\Items\Ports\DbPurchasableItemRepository;
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
