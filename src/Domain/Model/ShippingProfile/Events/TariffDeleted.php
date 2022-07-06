<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\ShippingProfile\Events;

use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfileId;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\TariffId;

class TariffDeleted
{
    public function __construct(public readonly ShippingProfileId $shippingProfileId, public readonly TariffId $tariffId)
    {
    }
}
