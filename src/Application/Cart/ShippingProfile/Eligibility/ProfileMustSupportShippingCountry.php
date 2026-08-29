<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Cart\ShippingProfile\Eligibility;

use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfile;

final class ProfileMustSupportShippingCountry implements ShippingProfileEligibilityRule
{
    public function isEligible(Order $order, ShippingProfile $shippingProfile): bool
    {
        if (! $shippingProfile->hasAnyCountries()) {
            return true;
        }

        $shippingCountryId = $order->getShippingAddress()?->getAddress()->countryId;

        return $shippingCountryId !== null && $shippingProfile->hasCountry($shippingCountryId);
    }
}
