<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjusters;

use Thinktomorrow\Trader\Domain\Common\Price\ConvertsToMoney;
use Thinktomorrow\Trader\Domain\Model\Country\Country;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingId;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfile;

interface FindSuitableShippingProfile
{
    public function findMatch(ShippingId $shippingId, ConvertsToMoney $subTotal, Country $country, \DateTimeImmutable $date): ShippingProfile;
}
