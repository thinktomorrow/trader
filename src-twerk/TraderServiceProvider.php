<?php

namespace Thinktomorrow\Trader;

use Illuminate\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;
use Thinktomorrow\Trader\Order\Domain\OrderState;
use Thinktomorrow\Trader\Cart\Domain\CartRepository;
use Thinktomorrow\Trader\Order\Domain\OrderStateMachine;
use Thinktomorrow\Trader\Order\Ports\DefaultOrderStateMachine;
use Thinktomorrow\Trader\Catalog\Products\Domain\DefaultProduct;
use Thinktomorrow\Trader\Catalog\Products\Domain\Product;
use Thinktomorrow\Trader\Catalog\Products\Domain\ProductRepository;
use Thinktomorrow\Trader\Catalog\Products\Domain\ProductStateMachine;
use Thinktomorrow\Trader\Catalog\Products\Domain\ProductGroupStateMachine;
use Thinktomorrow\Trader\Catalog\Products\Ports\DefaultProductStateMachine;
use Thinktomorrow\Trader\Catalog\Products\Ports\Laravel\DbCatalogRepository;
use Thinktomorrow\Trader\Catalog\Products\Ports\Laravel\DbProductRepository;
use Thinktomorrow\Trader\Catalog\Products\Reads\CatalogRepository;
use Thinktomorrow\Trader\Catalog\Products\Reads\DefaultProductRead;
use Thinktomorrow\Trader\Catalog\Products\Reads\ProductRead;
use Thinktomorrow\Trader\Common\Domain\References\DefaultReferenceValue;
use Thinktomorrow\Trader\Common\Domain\References\ReferenceValue;
use Thinktomorrow\Trader\Common\Events\EventDispatcher;
use Thinktomorrow\Trader\Order\Domain\CartItem;
use Thinktomorrow\Trader\Order\Domain\CartItemCollection;
use Thinktomorrow\Trader\Order\Domain\CurrentCartSource;
use Thinktomorrow\Trader\Order\Domain\DefaultCartItem;
use Thinktomorrow\Trader\Order\Domain\DefaultCartItemCollection;
use Thinktomorrow\Trader\Order\Domain\Order;
use Thinktomorrow\Trader\Order\Domain\OrderReferenceSource;
use Thinktomorrow\Trader\Project\Defaults\CartDefaults;
use Thinktomorrow\Trader\Purchase\Cart\Ports\DbCartRepository;
use Thinktomorrow\Trader\Purchase\Cart\Ports\DbCurrentCartSource;
use Thinktomorrow\Trader\Purchase\Cart\Ports\LaravelCookieCartReferenceSource;
use Thinktomorrow\Trader\Purchase\Items\Domain\PurchasableItemRepository;
use Thinktomorrow\Trader\Purchase\Items\Ports\DbPurchasableItemRepository;
use Thinktomorrow\Trader\Purchase\Notes\Domain\NoteCollection;
use Thinktomorrow\Trader\Purchase\Notes\Ports\DefaultNoteCollection;
use Thinktomorrow\Trader\Catalog\Products\Ports\DefaultProductGroupStateMachine;

class TraderServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        /*
         * --------------------------------------------------
         * STATE MACHINES
         * --------------------------------------------------
         */
        $this->app->bind(OrderStateMachine::class, DefaultOrderStateMachine::class);
        $this->app->bind(ProductStateMachine::class, DefaultProductStateMachine::class);
        $this->app->bind(ProductGroupStateMachine::class, DefaultProductGroupStateMachine::class);


        // OLD REGISTER: ...

//        $this->app->bind(EventDispatcher::class, Dispatcher::class);
//
//        /*
//         * --------------------------------------------------
//         * FIND
//         * --------------------------------------------------
//         */
//        $this->app->bind(Product::class, DefaultProduct::class);
//        $this->app->bind(ProductRead::class, DefaultProductRead::class);
//        $this->app->bind(CatalogRepository::class, DbCatalogRepository::class);
//        $this->app->bind(ProductRepository::class, DbProductRepository::class);
//
//        /*
//         * --------------------------------------------------
//         * PURCHASE
//         * --------------------------------------------------
//         */
//        $this->app->bind(NoteCollection::class, DefaultNoteCollection::class);
//        $this->app->bind(Order::class, CartDefaults::class);
//        $this->app->bind(CartItem::class, DefaultCartItem::class);
//        $this->app->bind(CartItemCollection::class, DefaultCartItemCollection::class);
//        $this->app->bind(CartRepository::class, DbCartRepository::class);
//        $this->app->bind(ReferenceValue::class, DefaultReferenceValue::class);
//        $this->app->bind(OrderReferenceSource::class, LaravelCookieCartReferenceSource::class);
//        $this->app->singleton(CurrentCartSource::class, DbCurrentCartSource::class);
//        $this->app->bind(PurchasableItemRepository::class, DbPurchasableItemRepository::class);
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
