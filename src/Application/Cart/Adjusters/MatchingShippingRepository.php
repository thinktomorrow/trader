<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Cart\Adjusters;

use Thinktomorrow\Trader\Domain\Model\Shipping\ShippingId;
use Thinktomorrow\Trader\Domain\Model\Order\Price\SubTotal;
use Thinktomorrow\Trader\Domain\Model\Order\Details\Shipping;
use Thinktomorrow\Trader\Domain\Model\Shipping\ShippingCountry;

interface MatchingShippingRepository
{
    public function findMatch(ShippingId $shippingId, SubTotal $subTotal, ShippingCountry $country, \DateTimeImmutable $date): Shipping;
}
