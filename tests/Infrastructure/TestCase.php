<?php
declare(strict_types=1);

namespace Tests\Infrastructure;

use Tests\TestHelpers;
use Thinktomorrow\Trader\Application\Cart\Read\Cart;
use Thinktomorrow\Trader\Application\Cart\Read\CartBillingAddress;
use Thinktomorrow\Trader\Application\Cart\Read\CartLine;
use Thinktomorrow\Trader\Application\Cart\Read\CartPayment;
use Thinktomorrow\Trader\Application\Cart\Read\CartShipping;
use Thinktomorrow\Trader\Application\Cart\Read\CartShippingAddress;
use Thinktomorrow\Trader\Application\Cart\Read\CartShopper;
use Thinktomorrow\Trader\Application\Cart\VariantForCart\VariantForCart;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonNode;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\Cart\DefaultCart;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\Cart\DefaultCartBillingAddress;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\Cart\DefaultCartLine;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\Cart\DefaultCartPayment;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\Cart\DefaultCartShipping;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\Cart\DefaultCartShippingAddress;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\Cart\DefaultCartShopper;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\DefaultTaxonNode;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\DefaultVariantForCart;
use Thinktomorrow\Trader\Infrastructure\Laravel\TraderServiceProvider;
use Thinktomorrow\Trader\Infrastructure\Shop\ShopServiceProvider;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryOrderRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryProductRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryTaxonRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryVariantRepository;
use Thinktomorrow\Trader\Infrastructure\Test\TestContainer;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    use TestHelpers;

    protected function setUp(): void
    {
        parent::setUp();

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
        (new InMemoryOrderRepository())->clear();
        (new InMemoryProductRepository())->clear();
        (new InMemoryVariantRepository())->clear();
        (new InMemoryTaxonRepository())->clear();

        parent::tearDown();
    }
}
