<?php

namespace Thinktomorrow\Trader\Tests\Features;

use PHPUnit\Framework\TestCase;
use Thinktomorrow\Trader\Discounts\Application\Reads\Discount;
use Thinktomorrow\Trader\Orders\Application\OrderAssembler;
use Thinktomorrow\Trader\Orders\Domain\Read\Cart;
use Thinktomorrow\Trader\Orders\Domain\Read\MerchantItem as MerchantItemContract;
use Thinktomorrow\Trader\Orders\Domain\Read\MerchantOrder as MerchantOrderContract;
use Thinktomorrow\Trader\Tests\Unit\InMemoryOrderRepository;
use Thinktomorrow\Trader\Orders\Ports\Read\MerchantItem;
use Thinktomorrow\Trader\Orders\Ports\Read\MerchantOrder;
use Thinktomorrow\Trader\Tests\IlluminateContainer;

class FeatureTestCase extends TestCase
{
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
            return new \Thinktomorrow\Trader\Discounts\Ports\Read\Discount();
        });
    }
}
