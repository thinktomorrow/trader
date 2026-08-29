<?php

declare(strict_types=1);

namespace Tests\Acceptance\Cart;

use Thinktomorrow\Trader\Application\Cart\ShippingProfile\AvailableShippingProfilesForOrder;
use Thinktomorrow\Trader\Application\Cart\ShippingProfile\Eligibility\ShippingProfileEligibility;
use Thinktomorrow\Trader\Application\Cart\ShippingProfile\Eligibility\ShippingProfileEligibilityRule;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfile;

final class AvailableShippingProfilesForOrderTest extends CartContext
{
    public function test_it_silently_filters_profiles_using_order_eligibility_rules(): void
    {
        $this->givenShippingCostsForAPurchaseOfEur(2, 0, 10, [], 'shipping');
        $repository = $this->orderContext->repos()->shippingProfileRepository();
        $eligibility = new ShippingProfileEligibility(new class implements ShippingProfileEligibilityRule
        {
            public function isEligible(Order $order, ShippingProfile $shippingProfile): bool
            {
                return false;
            }
        });

        $profiles = (new AvailableShippingProfilesForOrder(
            $this->orderContext->repos()->orderRepository(),
            $repository,
            $repository,
            $eligibility,
        ))->get($this->getOrder()->orderId);

        $this->assertSame([], $profiles);
    }
}
