<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel;

use Thinktomorrow\Trader\TraderConfig;
use Illuminate\Support\ServiceProvider;
use Thinktomorrow\Trader\Domain\Model\Order\OrderRepository;
use Thinktomorrow\Trader\Domain\Common\Event\EventDispatcher;
use Thinktomorrow\Trader\Application\Product\Grid\GridRepository;
use Thinktomorrow\Trader\Domain\Model\Customer\CustomerRepository;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethodRepository;
use Thinktomorrow\Trader\Domain\Model\CustomerLogin\CustomerLoginRepository;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfileRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlGridRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlOrderRepository;
use Thinktomorrow\Trader\Application\Product\ProductDetail\ProductDetailRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlFindVariantForCart;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlCustomerRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlProductDetailRepository;
use Thinktomorrow\Trader\Application\Cart\VariantForCart\FindVariantForCart;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlPaymentMethodRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlCustomerLoginRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlShippingProfileRepository;

class TraderServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Trader
        $this->app->bind(TraderConfig::class, \Thinktomorrow\Trader\Infrastructure\Laravel\config\TraderConfig::class);
        $this->app->bind(EventDispatcher::class, \Thinktomorrow\Trader\Infrastructure\Laravel\Services\EventDispatcher::class);

        // Product
        $this->app->bind(GridRepository::class, MysqlGridRepository::class);
        $this->app->bind(ProductDetailRepository::class, MysqlProductDetailRepository::class);
        $this->app->bind(FindVariantForCart::class, MysqlFindVariantForCart::class);

        // Order
        $this->app->bind(OrderRepository::class, MysqlOrderRepository::class);
        $this->app->bind(ShippingProfileRepository::class, MysqlShippingProfileRepository::class);
        $this->app->bind(PaymentMethodRepository::class, MysqlPaymentMethodRepository::class);
        $this->app->bind(CustomerRepository::class, MysqlCustomerRepository::class);
        $this->app->bind(CustomerLoginRepository::class, MysqlCustomerLoginRepository::class);
    }

    public function boot()
    {
        // Config
        $this->publishes([__DIR__.'/config/config.php' => config_path('trader.php')]);
        $this->mergeConfigFrom(__DIR__.'/config/config.php', 'trader');

        // Migrations
         $this->loadMigrationsFrom(__DIR__.'/database/migrations');
    }
}
