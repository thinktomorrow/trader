<?php

namespace Thinktomorrow\Trader\Tests\Unit;

use Thinktomorrow\Trader\Shipment\Domain\ShippingMethod;
use Thinktomorrow\Trader\Shipment\Domain\ShippingMethodId;
use Thinktomorrow\Trader\Shipment\Ports\Persistence\InMemoryShippingMethodRepository;

class ShippingMethodRepositoryTest extends UnitTestCase
{
    /** @test */
    public function it_can_find_a_shippingMethod()
    {
        $shippingMethod = new ShippingMethod(ShippingMethodId::fromInteger(3));
        $repo = new InMemoryShippingMethodRepository();

        $repo->add($shippingMethod);

        $this->assertEquals($shippingMethod, $repo->find(ShippingMethodId::fromInteger(3)));
    }

    public function it_throws_exception_if_order_does_not_exist()
    {
        $this->setExpectedException(\RuntimeException::class);

        $repo = new InMemoryShippingMethodRepository();
        $repo->find(ShippingMethodId::fromInteger(3));
    }
}
