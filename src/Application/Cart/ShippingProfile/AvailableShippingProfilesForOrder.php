<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Cart\ShippingProfile;

use Thinktomorrow\Trader\Application\Cart\ShippingProfile\Eligibility\ShippingProfileEligibility;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Order\OrderRepository;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfileId;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfileRepository;

final class AvailableShippingProfilesForOrder
{
    public function __construct(
        private OrderRepository $orderRepository,
        private ShippingProfileRepository $shippingProfileRepository,
        private ShippingProfileForCartRepository $shippingProfileForCartRepository,
        private ShippingProfileEligibility $shippingProfileEligibility,
    ) {}

    /** @return ShippingProfileForCart[] */
    public function get(OrderId $orderId): array
    {
        $order = $this->orderRepository->findForCart($orderId);
        $countryId = $order->getShippingAddress()?->getAddress()->countryId?->get();
        $candidates = $this->shippingProfileForCartRepository->findAllShippingProfilesForCart($countryId);

        return array_values(array_filter(
            $candidates,
            function (ShippingProfileForCart $candidate) use ($order): bool {
                $shippingProfile = $this->shippingProfileRepository->find(
                    ShippingProfileId::fromString($candidate->getShippingProfileId())
                );

                return $this->shippingProfileEligibility->isEligible($order, $shippingProfile);
            }
        ));
    }
}
