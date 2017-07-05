<?php

namespace Thinktomorrow\Trader\Tests\Unit;

use Thinktomorrow\Trader\Shipment\Domain\ShipmentId;
use Thinktomorrow\Trader\Shipment\Ports\Persistence\InMemoryShippingMethodRepository;
use Thinktomorrow\Trader\Shipment\Domain\ShippingMethod;
use Thinktomorrow\Trader\Shipment\Domain\ShippingMethodId;

class ShippingMethodRepositoryTest extends UnitTestCase
{
    /** @test */
    function it_can_find_a_shippingMethod()
    {
        $shippingMethod = new ShippingMethod(ShippingMethodId::fromInteger(3));
        $repo = new InMemoryShippingMethodRepository();

        $repo->add($shippingMethod);

        $this->assertEquals($shippingMethod, $repo->find(ShippingMethodId::fromInteger(3)));
    }

    function it_throws_exception_if_order_does_not_exist()
    {
        $this->setExpectedException(\RuntimeException::class);

        $repo = new InMemoryShippingMethodRepository();
        $repo->find(ShippingMethodId::fromInteger(3));
    }
}