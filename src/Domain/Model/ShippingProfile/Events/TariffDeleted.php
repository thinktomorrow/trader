<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\ShippingProfile\Events;

use Thinktomorrow\Trader\Domain\Model\ShippingProfile\TariffId;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfileId;

class TariffDeleted
{
    public function __construct(public readonly ShippingProfileId $shippingProfileId, public readonly TariffId $tariffId)
    {

    }
}
