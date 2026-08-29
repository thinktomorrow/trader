<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Cart\ShippingProfile\Eligibility;

use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfile;

interface ShippingProfileEligibilityRule
{
    public function isEligible(Order $order, ShippingProfile $shippingProfile): bool;
}
