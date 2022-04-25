<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel;

use Illuminate\Support\Arr;
use Thinktomorrow\Trader\TraderConfig;
use Illuminate\Support\ServiceProvider;
use Thinktomorrow\Trader\Application\Common\DataRenderer;
use Thinktomorrow\Trader\Application\Common\DefaultLocale;
use Thinktomorrow\Trader\Domain\Model\Order\OrderRepository;
use Thinktomorrow\Trader\Domain\Common\Event\EventDispatcher;
use Thinktomorrow\Trader\Domain\Model\Product\ProductRepository;
use Thinktomorrow\Trader\Domain\Model\Product\VariantRepository;
use Thinktomorrow\Trader\Application\Product\Grid\GridRepository;
use Thinktomorrow\Trader\Domain\Model\Customer\CustomerRepository;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonTreeRepository;
use Thinktomorrow\Trader\Application\Taxon\Category\CategoryRepository;
use Thinktomorrow\Trader\Infrastructure\Vine\VineTaxonIdOptionsComposer;
use Thinktomorrow\Trader\Infrastructure\Vine\VineTaxonFilterTreeComposer;
use Thinktomorrow\Trader\Application\Product\ProductDetail\ProductDetail;
use Thinktomorrow\Trader\Application\Taxon\Filter\TaxonFilterTreeComposer;
use Thinktomorrow\Trader\Infrastructure\Vine\VineFlattenedTaxonIdsComposer;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethodRepository;
use Thinktomorrow\Trader\Domain\Model\CustomerLogin\CustomerLoginRepository;
use Thinktomorrow\Trader\Application\Product\Grid\FlattenedTaxonIdsComposer;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\DefaultProductDetail;
use Thinktomorrow\Trader\Application\Taxon\TaxonSelect\TaxonIdOptionsComposer;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfileRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlGridRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlOrderRepository;
use Thinktomorrow\Trader\Application\Cart\VariantForCart\VariantForCartRepository;
use Thinktomorrow\Trader\Application\Product\ProductDetail\ProductDetailRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlVariantRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlProductRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlCustomerRepository;
use Thinktomorrow\Trader\Application\Product\ProductOptions\ProductOptionsRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlTaxonTreeRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlProductDetailRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlPaymentMethodRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlCustomerLoginRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlShippingProfileRepository;
use Thinktomorrow\Trader\Application\Product\ProductOptions\VariantProductOptionsRepository;

class TraderServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Trader
        $this->app->bind(TraderConfig::class, \Thinktomorrow\Trader\Infrastructure\Laravel\config\TraderConfig::class);
        $this->app->bind(EventDispatcher::class, \Thinktomorrow\Trader\Infrastructure\Laravel\Services\EventDispatcher::class);

        // Product
        $this->app->bind(GridRepository::class, MysqlGridRepository::class);
        $this->app->bind(ProductRepository::class, MysqlProductRepository::class);
        $this->app->bind(ProductDetailRepository::class, MysqlProductDetailRepository::class);
        $this->app->bind(ProductOptionsRepository::class, MysqlProductDetailRepository::class);
        $this->app->bind(VariantRepository::class, MysqlVariantRepository::class);
        $this->app->bind(VariantForCartRepository::class, MysqlVariantRepository::class);
        $this->app->bind(VariantProductOptionsRepository::class, MysqlVariantRepository::class);
        $this->app->bind(ProductDetail::class, function(){
            return DefaultProductDetail::class;
        });

        // Taxon
        $this->app->bind(TaxonTreeRepository::class, MysqlTaxonTreeRepository::class);
        $this->app->bind(CategoryRepository::class, MysqlTaxonTreeRepository::class);
        $this->app->bind(TaxonIdOptionsComposer::class, VineTaxonIdOptionsComposer::class);
        $this->app->bind(TaxonFilterTreeComposer::class, VineTaxonFilterTreeComposer::class);
        $this->app->bind(FlattenedTaxonIdsComposer::class, VineFlattenedTaxonIdsComposer::class);

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

        // Default locale
        DefaultLocale::set($this->app->make(TraderConfig::class)->getDefaultLocale());

        /**
         * This closure deals with rendering string and localized content on our view and read models.
         *
         * Dotted syntax for nested arrays is supported, e.g. customer.firstname. This function
         * expects that localized content is always formatted as <key>.<language>. We always
         * first try to find localized content before fetching the defaults.
         */
        DataRenderer::setDataResolver(function(array $data, string $key, string $language = null, $default = null)
        {
            if(!$language) {
                $language = $this->app->make(TraderConfig::class)
                    ->getDefaultLocale()
                    ->getLanguage();
            }

            return Arr::get(
                $data, $key . '.' . $language, Arr::get($data, $key, $default)
            );
        });
    }
}
