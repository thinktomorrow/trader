<?php

namespace Thinktomorrow\Trader\Shipment\Domain;

interface ShippingMethodRepository
{
    public function add(ShippingMethod $shippingMethod);

    public function find(ShippingMethodId $shippingMethodId): ShippingMethod;
}
