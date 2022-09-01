<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel;

use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;
use Thinktomorrow\Trader\Application\Cart\Read\Cart;
use Thinktomorrow\Trader\Application\Cart\Read\CartBillingAddress;
use Thinktomorrow\Trader\Application\Cart\Read\CartDiscount;
use Thinktomorrow\Trader\Application\Cart\Read\CartLine;
use Thinktomorrow\Trader\Application\Cart\Read\CartLinePersonalisation;
use Thinktomorrow\Trader\Application\Cart\Read\CartPayment;
use Thinktomorrow\Trader\Application\Cart\Read\CartRepository;
use Thinktomorrow\Trader\Application\Cart\Read\CartShipping;
use Thinktomorrow\Trader\Application\Cart\Read\CartShippingAddress;
use Thinktomorrow\Trader\Application\Cart\Read\CartShopper;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderLogEntry;
use Thinktomorrow\Trader\Application\Cart\ShippingProfile\ShippingProfileForCart;
use Thinktomorrow\Trader\Application\Cart\ShippingProfile\ShippingProfileForCartRepository;
use Thinktomorrow\Trader\Application\Cart\VariantForCart\VariantForCart;
use Thinktomorrow\Trader\Application\Cart\VariantForCart\VariantForCartRepository;
use Thinktomorrow\Trader\Application\Common\DataRenderer;
use Thinktomorrow\Trader\Application\Common\DefaultLocale;
use Thinktomorrow\Trader\Application\Country\BillingCountryRepository;
use Thinktomorrow\Trader\Application\Country\ShippingCountryRepository;
use Thinktomorrow\Trader\Application\Customer\Read\CustomerBillingAddress;
use Thinktomorrow\Trader\Application\Customer\Read\CustomerRead;
use Thinktomorrow\Trader\Application\Customer\Read\CustomerReadRepository;
use Thinktomorrow\Trader\Application\Customer\Read\CustomerShippingAddress;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrder;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderBillingAddress;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderDiscount;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderLine;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderLinePersonalisation;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderPayment;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderRepository;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderShipping;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderShippingAddress;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderShopper;
use Thinktomorrow\Trader\Application\Product\CheckProductOptions\CheckProductOptionsRepository;
use Thinktomorrow\Trader\Application\Product\Grid\FlattenedTaxonIdsComposer;
use Thinktomorrow\Trader\Application\Product\Grid\GridItem;
use Thinktomorrow\Trader\Application\Product\Grid\GridRepository;
use Thinktomorrow\Trader\Application\Product\Personalisations\PersonalisationField;
use Thinktomorrow\Trader\Application\Product\ProductDetail\ProductDetail;
use Thinktomorrow\Trader\Application\Product\ProductDetail\ProductDetailRepository;
use Thinktomorrow\Trader\Application\Product\VariantLinks\VariantLink;
use Thinktomorrow\Trader\Application\Promo\OrderPromo\Conditions\MinimumAmountOrderCondition;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\MerchantOrder\DefaultMerchantOrderLogEntry;
use Thinktomorrow\Trader\Application\Promo\OrderPromo\Conditions\MinimumLinesQuantityOrderCondition;
use Thinktomorrow\Trader\Application\Promo\OrderPromo\Discounts\FixedAmountOrderDiscount;
use Thinktomorrow\Trader\Application\Promo\OrderPromo\Discounts\PercentageOffOrderDiscount;
use Thinktomorrow\Trader\Application\Promo\OrderPromo\OrderConditionFactory;
use Thinktomorrow\Trader\Application\Promo\OrderPromo\OrderDiscountFactory;
use Thinktomorrow\Trader\Application\Promo\OrderPromo\OrderPromoRepository;
use Thinktomorrow\Trader\Application\Taxon\Category\CategoryRepository;
use Thinktomorrow\Trader\Application\Taxon\Filter\TaxonFilterTreeComposer;
use Thinktomorrow\Trader\Application\Taxon\Redirect\RedirectRepository;
use Thinktomorrow\Trader\Application\Taxon\TaxonSelect\TaxonIdOptionsComposer;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonNode;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonTreeRepository;
use Thinktomorrow\Trader\Domain\Common\Event\EventDispatcher;
use Thinktomorrow\Trader\Domain\Common\Taxes\TaxRate;
use Thinktomorrow\Trader\Domain\Model\Country\CountryRepository;
use Thinktomorrow\Trader\Domain\Model\Customer\CustomerRepository;
use Thinktomorrow\Trader\Domain\Model\CustomerLogin\CustomerLoginRepository;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountTotal;
use Thinktomorrow\Trader\Domain\Model\Order\OrderRepository;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\PaymentState;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\PaymentStateMachine;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\PaymentStateToEventMap;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingState;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingStateMachine;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingStateToEventMap;
use Thinktomorrow\Trader\Domain\Model\Order\State\OrderState;
use Thinktomorrow\Trader\Domain\Model\Order\State\OrderStateMachine;
use Thinktomorrow\Trader\Domain\Model\Order\State\OrderStateToEventMap;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethodRepository;
use Thinktomorrow\Trader\Domain\Model\Product\ProductRepository;
use Thinktomorrow\Trader\Domain\Model\Product\VariantRepository;
use Thinktomorrow\Trader\Domain\Model\Promo\ConditionFactory;
use Thinktomorrow\Trader\Domain\Model\Promo\Conditions\MinimumAmount;
use Thinktomorrow\Trader\Domain\Model\Promo\Conditions\MinimumLinesQuantity;
use Thinktomorrow\Trader\Domain\Model\Promo\DiscountFactory;
use Thinktomorrow\Trader\Domain\Model\Promo\Discounts\FixedAmountDiscount;
use Thinktomorrow\Trader\Domain\Model\Promo\Discounts\PercentageOffDiscount;
use Thinktomorrow\Trader\Domain\Model\Promo\PromoRepository;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfileRepository;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\Cart\DefaultCart;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\Cart\DefaultCartBillingAddress;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\Cart\DefaultCartDiscount;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\Cart\DefaultCartLine;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\Cart\DefaultCartLinePersonalisation;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\Cart\DefaultCartPayment;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\Cart\DefaultCartShipping;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\Cart\DefaultCartShippingAddress;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\Cart\DefaultCartShopper;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\CustomerRead\DefaultCustomerBillingAddress;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\CustomerRead\DefaultCustomerRead;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\CustomerRead\DefaultCustomerShippingAddress;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\DefaultGridItem;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\DefaultOrderGridItem;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\DefaultPersonalisationField;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\DefaultProductDetail;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\DefaultShippingProfileForCart;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\DefaultTaxonNode;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\DefaultVariantForCart;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\DefaultVariantLink;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\MerchantOrder\DefaultMerchantOrder;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\MerchantOrder\DefaultMerchantOrderBillingAddress;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\MerchantOrder\DefaultMerchantOrderDiscount;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\MerchantOrder\DefaultMerchantOrderLine;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\MerchantOrder\DefaultMerchantOrderLinePersonalisation;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\MerchantOrder\DefaultMerchantOrderPayment;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\MerchantOrder\DefaultMerchantOrderShipping;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\MerchantOrder\DefaultMerchantOrderShippingAddress;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\MerchantOrder\DefaultMerchantOrderShopper;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlCartRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlCheckProductOptionsRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlCountryRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlCustomerLoginRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlCustomerRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlGridRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlMerchantOrderRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlOrderGridRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlOrderRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlPaymentMethodRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlProductDetailRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlProductRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlPromoRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlRedirectRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlShippingProfileRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlTaxonRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlTaxonTreeRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlVariantRepository;
use Thinktomorrow\Trader\Infrastructure\Vine\VineFlattenedTaxonIdsComposer;
use Thinktomorrow\Trader\Infrastructure\Vine\VineTaxonFilterTreeComposer;
use Thinktomorrow\Trader\Infrastructure\Vine\VineTaxonIdOptionsComposer;
use Thinktomorrow\Trader\TraderConfig;

class TraderServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Trader
        $this->app->bind(TraderConfig::class, \Thinktomorrow\Trader\Infrastructure\Laravel\config\TraderConfig::class);
        $this->app->bind(EventDispatcher::class, \Thinktomorrow\Trader\Infrastructure\Laravel\Services\EventDispatcher::class);

        // Catalog Repositories
        $this->app->bind(GridRepository::class, MysqlGridRepository::class);
        $this->app->bind(ProductRepository::class, MysqlProductRepository::class);
        $this->app->bind(ProductDetailRepository::class, MysqlProductDetailRepository::class);
        $this->app->bind(VariantRepository::class, MysqlVariantRepository::class);
        $this->app->bind(VariantForCartRepository::class, MysqlVariantRepository::class);
        $this->app->bind(CheckProductOptionsRepository::class, MysqlCheckProductOptionsRepository::class);
        $this->app->bind(TaxonRepository::class, MysqlTaxonRepository::class);
        $this->app->bind(TaxonTreeRepository::class, MysqlTaxonTreeRepository::class);
        $this->app->bind(CategoryRepository::class, MysqlTaxonTreeRepository::class);
        $this->app->bind(TaxonIdOptionsComposer::class, VineTaxonIdOptionsComposer::class);
        $this->app->bind(TaxonFilterTreeComposer::class, VineTaxonFilterTreeComposer::class);
        $this->app->bind(FlattenedTaxonIdsComposer::class, VineFlattenedTaxonIdsComposer::class);
        $this->app->bind(RedirectRepository::class, MysqlRedirectRepository::class);

        // Order repositories
        $this->app->bind(ShippingCountryRepository::class, MysqlShippingProfileRepository::class);
        $this->app->bind(BillingCountryRepository::class, MysqlCountryRepository::class);
        $this->app->bind(CountryRepository::class, MysqlCountryRepository::class);
        $this->app->bind(CartRepository::class, MysqlCartRepository::class);
        $this->app->bind(ShippingProfileForCartRepository::class, MysqlShippingProfileRepository::class);
        $this->app->bind(PromoRepository::class, MysqlPromoRepository::class);
        $this->app->bind(OrderPromoRepository::class, MysqlPromoRepository::class);
        $this->app->bind(OrderRepository::class, MysqlOrderRepository::class);
        $this->app->bind(ShippingProfileRepository::class, MysqlShippingProfileRepository::class);
        $this->app->bind(PaymentMethodRepository::class, MysqlPaymentMethodRepository::class);
        $this->app->bind(MerchantOrderRepository::class, MysqlMerchantOrderRepository::class);
        $this->app->bind(\Thinktomorrow\Trader\Application\Order\Grid\OrderGridRepository::class, MysqlOrderGridRepository::class);

        // Customer repositories
        $this->app->bind(CustomerRepository::class, MysqlCustomerRepository::class);
        $this->app->bind(CustomerLoginRepository::class, MysqlCustomerLoginRepository::class);
        $this->app->bind(CustomerReadRepository::class, MysqlCustomerRepository::class);

        // Product models
        $this->app->bind(GridItem::class, fn () => DefaultGridItem::class);
        $this->app->bind(ProductDetail::class, fn () => DefaultProductDetail::class);
        $this->app->bind(VariantLink::class, fn () => DefaultVariantLink::class);
        $this->app->bind(PersonalisationField::class, fn () => DefaultPersonalisationField::class);
        $this->app->bind(TaxonNode::class, fn () => DefaultTaxonNode::class);
        $this->app->bind(VariantForCart::class, fn () => DefaultVariantForCart::class);

        // Order models
        $this->app->bind(\Thinktomorrow\Trader\Application\Order\Grid\OrderGridItem::class, fn () => DefaultOrderGridItem::class);
        $this->app->bind(Cart::class, fn () => DefaultCart::class);
        $this->app->bind(CartLine::class, fn () => DefaultCartLine::class);
        $this->app->bind(CartLinePersonalisation::class, fn () => DefaultCartLinePersonalisation::class);
        $this->app->bind(CartDiscount::class, fn () => DefaultCartDiscount::class);
        $this->app->bind(CartShippingAddress::class, fn () => DefaultCartShippingAddress::class);
        $this->app->bind(CartBillingAddress::class, fn () => DefaultCartBillingAddress::class);
        $this->app->bind(CartShopper::class, fn () => DefaultCartShopper::class);
        $this->app->bind(CartPayment::class, fn () => DefaultCartPayment::class);
        $this->app->bind(CartShipping::class, fn () => DefaultCartShipping::class);
        $this->app->bind(ShippingProfileForCart::class, fn () => DefaultShippingProfileForCart::class);

        // MerchantOrder models
        $this->app->bind(MerchantOrder::class, fn () => DefaultMerchantOrder::class);
        $this->app->bind(MerchantOrderLine::class, fn () => DefaultMerchantOrderLine::class);
        $this->app->bind(MerchantOrderLinePersonalisation::class, fn () => DefaultMerchantOrderLinePersonalisation::class);
        $this->app->bind(MerchantOrderDiscount::class, fn () => DefaultMerchantOrderDiscount::class);
        $this->app->bind(MerchantOrderShippingAddress::class, fn () => DefaultMerchantOrderShippingAddress::class);
        $this->app->bind(MerchantOrderBillingAddress::class, fn () => DefaultMerchantOrderBillingAddress::class);
        $this->app->bind(MerchantOrderShopper::class, fn () => DefaultMerchantOrderShopper::class);
        $this->app->bind(MerchantOrderShipping::class, fn () => DefaultMerchantOrderShipping::class);
        $this->app->bind(MerchantOrderPayment::class, fn () => DefaultMerchantOrderPayment::class);
        $this->app->bind(MerchantOrderLogEntry::class, fn () => DefaultMerchantOrderLogEntry::class);

        // Customer models
        $this->app->bind(CustomerRead::class, fn () => DefaultCustomerRead::class);
        $this->app->bind(CustomerBillingAddress::class, fn () => DefaultCustomerBillingAddress::class);
        $this->app->bind(CustomerShippingAddress::class, fn () => DefaultCustomerShippingAddress::class);

        $this->registerPromoConditionsAndDiscounts();
        $this->registerStateMachines();
        $this->registerDefaultStateToEventMaps();
    }

    public function boot()
    {
        // Config
        $this->publishes([__DIR__.'/config/config.php' => config_path('trader.php')]);
        $this->mergeConfigFrom(__DIR__.'/config/config.php', 'trader');

        // Migrations
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');

        // Default discount tax rate
        DiscountTotal::setDiscountTaxRate(TaxRate::fromString($this->app->make(TraderConfig::class)->getDefaultTaxRate()));

        // Default locale
        DefaultLocale::set($this->app->make(TraderConfig::class)->getDefaultLocale());

        /**
         * This closure deals with rendering string and localized content on our view and read models.
         *
         * Dotted syntax for nested arrays is supported, e.g. customer.firstname. This function
         * expects that localized content is always formatted as <key>.<language>. We always
         * first try to find localized content before fetching the defaults.
         */
        DataRenderer::setDataResolver(function (array $data, string $key, string $language = null, $default = null) {
            $defaultLanguage = $this->app->make(TraderConfig::class)
                ->getDefaultLocale()
                ->getLanguage();

            if (! $language) {
                $language = $defaultLanguage;
            }

            $value = Arr::get(
                $data,
                $key . '.' . $language,
                Arr::get(
                    $data,
                    $key . '.' . $defaultLanguage,
                    Arr::get($data, $key, $default)
                )
            );

            return $value === null ? $default :$value;
        });
    }

    private function registerPromoConditionsAndDiscounts()
    {
        $this->app->bind(ConditionFactory::class, function () {
            return new ConditionFactory([
                MinimumLinesQuantity::class,
                MinimumAmount::class,
            ]);
        });

        $this->app->bind(DiscountFactory::class, function ($app) {
            return new DiscountFactory(
                [
                    PercentageOffDiscount::class,
                    FixedAmountDiscount::class,
                ],
                $app->get(ConditionFactory::class)
            );
        });

        $this->app->bind(OrderConditionFactory::class, function () {
            return new OrderConditionFactory([
                MinimumLinesQuantityOrderCondition::class,
                MinimumAmountOrderCondition::class,
            ]);
        });

        $this->app->bind(OrderDiscountFactory::class, function ($app) {
            return new OrderDiscountFactory(
                [
                    PercentageOffOrderDiscount::class,
                    FixedAmountOrderDiscount::class,
                ],
                $app->get(OrderConditionFactory::class)
            );
        });
    }

    private function registerStateMachines()
    {
        $this->app->bind(OrderStateMachine::class, function () {
            return new OrderStateMachine(OrderState::cases(), OrderState::getDefaultTransitions());
        });

        $this->app->bind(PaymentStateMachine::class, function () {
            return new PaymentStateMachine(PaymentState::cases(), PaymentState::getDefaultTransitions());
        });

        $this->app->bind(ShippingStateMachine::class, function () {
            return new ShippingStateMachine(ShippingState::cases(), ShippingState::getDefaultTransitions());
        });
    }

    private function registerDefaultStateToEventMaps()
    {
        OrderStateToEventMap::set(OrderStateToEventMap::getDefaultMapping());
        PaymentStateToEventMap::set(PaymentStateToEventMap::getDefaultMapping());
        ShippingStateToEventMap::set(ShippingStateToEventMap::getDefaultMapping());
    }
}
