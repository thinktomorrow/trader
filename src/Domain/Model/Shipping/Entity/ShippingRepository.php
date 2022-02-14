<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Shipping\Entity;

use Thinktomorrow\Trader\Domain\Model\Shipping\ShippingId;

interface ShippingRepository
{
    public function find(ShippingId $shippingId): Shipping;
}
