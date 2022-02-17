<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Shipping;

interface ShippingRepository
{
    public function save(Shipping $shipping): void;

    public function find(ShippingId $shippingId): Shipping;

    public function delete(ShippingId $shippingId): void;

    public function nextReference(): ShippingId;

    // TODO: this should be in application (where it is needed e.g. order-fulfillment department)
//    public function listUnfulfilledShipments(): array;
}
