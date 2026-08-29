<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Cart\ShippingProfile\Eligibility;

use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfile;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfileState;

final class ProfileMustBeOnline implements ShippingProfileEligibilityRule
{
    public function isEligible(Order $order, ShippingProfile $shippingProfile): bool
    {
        return in_array($shippingProfile->getState(), ShippingProfileState::onlineStates(), true);
    }
}
