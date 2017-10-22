<?php

namespace Thinktomorrow\Trader\Tests;

use PHPUnit\Framework\TestCase;
use Thinktomorrow\Trader\Discounts\Domain\Read\Discount;
use Thinktomorrow\Trader\Orders\Domain\Read\Cart;
use Thinktomorrow\Trader\Orders\Domain\Read\MerchantItem as MerchantItemContract;
use Thinktomorrow\Trader\Orders\Domain\Read\MerchantOrder as MerchantOrderContract;
use Thinktomorrow\Trader\Orders\Ports\Read\MerchantItem;
use Thinktomorrow\Trader\Orders\Ports\Read\MerchantOrder;
use Thinktomorrow\Trader\Tests\Stubs\InMemoryOrderRepository;

class FeatureTestCase extends TestCase
{
    use ShoppingHelpers;

    protected $container;

    public function setUp()
    {
        parent::setUp();

        $this->container = new IlluminateContainer();
        $this->addServicesToContainer();
    }

    protected function container($key)
    {
        return $this->container->make($key);
    }

    private function addServicesToContainer()
    {
        $this->container->bind('orderRepository', function ($c) {
            return new InMemoryOrderRepository();
        });

        $this->container->bind(MerchantOrderContract::class, function ($c) {
            return new MerchantOrder();
        });

        $this->container->bind(MerchantItemContract::class, function ($c) {
            return new MerchantItem();
        });

        $this->container->bind(Cart::class, function ($c, $params) {
            return new \Thinktomorrow\Trader\Orders\Ports\Read\Cart($params[0]);
        });

        $this->container->bind(Discount::class, function ($c) {
            return new \Thinktomorrow\Trader\Discounts\Ports\Reads\Discount();
        });

        $this->container->bind(OrderAssembler::class, function ($c) {
            return new OrderAssembler($c->make('orderRepository'));
        });
    }
}
