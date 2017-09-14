<?php

namespace Thinktomorrow\Trader\Tests\Features;

use PHPUnit\Framework\TestCase;
use Pimple\Container;
use Thinktomorrow\Trader\Order\Ports\Persistence\InMemoryOrderRepository;


class FeatureTestCase extends TestCase
{
    private $container;

    public function setUp()
    {
        parent::setUp();

        $this->container = new Container();

        $this->addServicesToContainer();
    }

    protected function container($key)
    {
        return $this->container[$key];
    }

    private function addServicesToContainer()
    {
        $this->container['orderRepository'] = function($c){
            return new InMemoryOrderRepository();
        };
    }

}