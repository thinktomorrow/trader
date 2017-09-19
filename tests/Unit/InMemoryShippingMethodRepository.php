<?php

namespace Thinktomorrow\Trader\Tests\Unit;

use Thinktomorrow\Trader\Shipment\Domain\ShippingMethod;
use Thinktomorrow\Trader\Shipment\Domain\ShippingMethodId;
use Thinktomorrow\Trader\Shipment\Domain\ShippingMethodRepository;

class InMemoryShippingMethodRepository implements ShippingMethodRepository
{
    private static $collection = [];

    public function find(ShippingMethodId $shippingMethodId): ShippingMethod
    {
        if (isset(self::$collection[(string) $shippingMethodId])) {
            return self::$collection[(string) $shippingMethodId];
        }

        throw new \RuntimeException('ShippingMethod not found by id ['.$shippingMethodId->get().']');
    }

    public function add(ShippingMethod $shippingMethod)
    {
        self::$collection[(string) $shippingMethod->id()] = $shippingMethod;
    }
}
