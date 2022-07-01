<?php
declare(strict_types=1);

namespace Tests\Infrastructure;

use Tests\TestHelpers;
use Thinktomorrow\Trader\Application\Cart\Read\Cart;
use Thinktomorrow\Trader\Application\Cart\Read\CartBillingAddress;
use Thinktomorrow\Trader\Application\Cart\Read\CartDiscount;
use Thinktomorrow\Trader\Application\Cart\Read\CartLine;
use Thinktomorrow\Trader\Application\Cart\Read\CartPayment;
use Thinktomorrow\Trader\Application\Cart\Read\CartShipping;
use Thinktomorrow\Trader\Application\Cart\Read\CartShippingAddress;
use Thinktomorrow\Trader\Application\Cart\Read\CartShopper;
use Thinktomorrow\Trader\Application\Cart\VariantForCart\VariantForCart;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrder;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderBillingAddress;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderDiscount;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderLine;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderPayment;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderShipping;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderShippingAddress;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderShopper;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonNode;
use Thinktomorrow\Trader\Domain\Common\Taxes\TaxRate;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountTotal;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\Cart\DefaultCart;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\Cart\DefaultCartBillingAddress;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\Cart\DefaultCartDiscount;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\Cart\DefaultCartLine;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\Cart\DefaultCartPayment;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\Cart\DefaultCartShipping;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\Cart\DefaultCartShippingAddress;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\Cart\DefaultCartShopper;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\DefaultTaxonNode;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\DefaultVariantForCart;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\MerchantOrder\DefaultMerchantOrder;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\MerchantOrder\DefaultMerchantOrderBillingAddress;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\MerchantOrder\DefaultMerchantOrderDiscount;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\MerchantOrder\DefaultMerchantOrderLine;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\MerchantOrder\DefaultMerchantOrderPayment;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\MerchantOrder\DefaultMerchantOrderShipping;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\MerchantOrder\DefaultMerchantOrderShippingAddress;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\MerchantOrder\DefaultMerchantOrderShopper;
use Thinktomorrow\Trader\Infrastructure\Laravel\TraderServiceProvider;
use Thinktomorrow\Trader\Infrastructure\Shop\ShopServiceProvider;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryOrderRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryProductRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryPromoRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryTaxonRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryVariantRepository;
use Thinktomorrow\Trader\Infrastructure\Test\TestContainer;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    use TestHelpers;

    protected function setUp(): void
    {
        parent::setUp();

        DiscountTotal::setDiscountTaxRate(TaxRate::fromString('21'));

        (new TestContainer())->add(TaxonNode::class, DefaultTaxonNode::class);
        (new TestContainer())->add(VariantForCart::class, DefaultVariantForCart::class);

        // Cart
        (new TestContainer())->add(Cart::class, DefaultCart::class);
        (new TestContainer())->add(CartLine::class, DefaultCartLine::class);
        (new TestContainer())->add(CartShippingAddress::class, DefaultCartShippingAddress::class);
        (new TestContainer())->add(CartBillingAddress::class, DefaultCartBillingAddress::class);
        (new TestContainer())->add(CartShipping::class, DefaultCartShipping::class);
        (new TestContainer())->add(CartPayment::class, DefaultCartPayment::class);
        (new TestContainer())->add(CartShopper::class, DefaultCartShopper::class);
        (new TestContainer())->add(CartDiscount::class, DefaultCartDiscount::class);

        // MerchantOrder
        (new TestContainer())->add(MerchantOrder::class, DefaultMerchantOrder::class);
        (new TestContainer())->add(MerchantOrderLine::class, DefaultMerchantOrderLine::class);
        (new TestContainer())->add(MerchantOrderShippingAddress::class, DefaultMerchantOrderShippingAddress::class);
        (new TestContainer())->add(MerchantOrderBillingAddress::class, DefaultMerchantOrderBillingAddress::class);
        (new TestContainer())->add(MerchantOrderShipping::class, DefaultMerchantOrderShipping::class);
        (new TestContainer())->add(MerchantOrderPayment::class, DefaultMerchantOrderPayment::class);
        (new TestContainer())->add(MerchantOrderShopper::class, DefaultMerchantOrderShopper::class);
        (new TestContainer())->add(MerchantOrderDiscount::class, DefaultMerchantOrderDiscount::class);
    }

    public function getPackageProviders($app)
    {
        return [
            TraderServiceProvider::class,
            ShopServiceProvider::class,
        ];
    }

    protected function tearDown(): void
    {
        InMemoryOrderRepository::clear();
        InMemoryProductRepository::clear();
        InMemoryVariantRepository::clear();
        InMemoryTaxonRepository::clear();
        InMemoryPromoRepository::clear();

        parent::tearDown();
    }
}
