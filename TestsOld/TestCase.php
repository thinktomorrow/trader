<?php

namespace Thinktomorrow\Trader\TestsOld;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Thinktomorrow\Trader\Discounts\Domain\Read\Discount;
use Thinktomorrow\Trader\TestsOld\Stubs\InMemoryOrderRepository;

class TestCase extends BaseTestCase
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

        $this->container->bind(Discount::class, function ($c) {
            return new \Thinktomorrow\Trader\Discounts\Ports\Reads\Discount();
        });

        $this->container->bind(OrderAssembler::class, function ($c) {
            return new OrderAssembler($c->make('orderRepository'));
        });
    }
}
