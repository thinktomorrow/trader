<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Cart\ShippingProfile\Eligibility;

use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\Exceptions\ShippingProfileIsNotAvailable;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfile;

final class ShippingProfileEligibility
{
    /** @var ShippingProfileEligibilityRule[] */
    private array $rules;

    public function __construct(ShippingProfileEligibilityRule ...$rules)
    {
        $this->rules = $rules;
    }

    public function isEligible(Order $order, ShippingProfile $shippingProfile): bool
    {
        foreach ($this->rules as $rule) {
            if (! $rule->isEligible($order, $shippingProfile)) {
                return false;
            }
        }

        return true;
    }

    public function assertEligible(Order $order, ShippingProfile $shippingProfile): void
    {
        if (! $this->isEligible($order, $shippingProfile)) {
            throw new ShippingProfileIsNotAvailable(
                'Shipping profile ['.$shippingProfile->shippingProfileId->get().'] is not available for order ['.$order->orderId->get().'].'
            );
        }
    }
}
