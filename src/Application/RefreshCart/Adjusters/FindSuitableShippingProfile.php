<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\RefreshCart\Adjusters;

use Thinktomorrow\Trader\Domain\Model\Order\Price\SubTotal;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingId;
use Thinktomorrow\Trader\Domain\Model\Order\Address\ShippingCountry;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfile;

interface FindSuitableShippingProfile
{
    public function findMatch(ShippingId $shippingId, SubTotal $subTotal, ShippingCountry $country, \DateTimeImmutable $date): ShippingProfile;
}
