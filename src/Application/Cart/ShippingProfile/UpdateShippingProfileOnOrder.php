<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Cart\ShippingProfile;

use Psr\Container\ContainerInterface;
use Thinktomorrow\Trader\Application\Cart\ShippingProfile\Eligibility\ProfileMustBeOnline;
use Thinktomorrow\Trader\Application\Cart\ShippingProfile\Eligibility\ProfileMustSupportShippingCountry;
use Thinktomorrow\Trader\Application\Cart\ShippingProfile\Eligibility\ShippingProfileEligibility;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Order\OrderRepository;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\Shipping;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingCost;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingState;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\Exceptions\CouldNotFindShippingProfile;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfile;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfileId;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfileRepository;

class UpdateShippingProfileOnOrder
{
    public function __construct(
        private ContainerInterface $container,
        private OrderRepository $orderRepository,
        private ShippingProfileRepository $shippingProfileRepository
    ) {}

    public function handle(Order $order, ShippingProfileId $shippingProfileId): void
    {
        $shippingProfile = $this->shippingProfileRepository->find($shippingProfileId);

        $this->shippingProfileEligibility()->assertEligible($order, $shippingProfile);
        $this->applyShippingProfile($order, $shippingProfile);
    }

    public function refresh(Order $order, ShippingProfileId $shippingProfileId): void
    {
        try {
            $shippingProfile = $this->shippingProfileRepository->find($shippingProfileId);
        } catch (CouldNotFindShippingProfile) {
            $this->removeAllShippingsFromOrder($order);

            return;
        }

        if (! $this->shippingProfileEligibility()->isEligible($order, $shippingProfile)) {
            $this->removeAllShippingsFromOrder($order);

            return;
        }

        $this->applyShippingProfile($order, $shippingProfile);
    }

    private function applyShippingProfile(Order $order, ShippingProfile $shippingProfile): void
    {
        // Apply matching tariff - if no tariff is found, no rate will be applied
        $tariff = $shippingProfile->findTariffByPrice($order->getSubtotalExcl());

        $shippingCost = ShippingCost::fromExcludingVat(
            $tariff ? $tariff->getRate() : Cash::zero(),
        );

        if (count($order->getShippings()) > 0) {
            /** @var Shipping $existingShipping */
            $existingShipping = $order->getShippings()[0];
            $existingShipping->updateShippingProfile($shippingProfile->shippingProfileId);
            $existingShipping->updateCost($shippingCost);
            $existingShipping->addData(array_merge($shippingProfile->getData(), ['requires_address' => $shippingProfile->requiresAddress()]));

            $order->updateShipping($existingShipping);
        } else {
            $shipping = Shipping::create(
                $order->orderId,
                $this->orderRepository->nextShippingReference(),
                $shippingProfile->shippingProfileId,
                $this->container->get(ShippingState::class)::getDefaultState(),
                $shippingCost
            );

            $shipping->addData(array_merge($shippingProfile->getData(), ['requires_address' => $shippingProfile->requiresAddress()]));

            $order->addShipping($shipping);
        }
    }

    private function removeAllShippingsFromOrder(Order $order): void
    {
        foreach ($order->getShippings() as $shipping) {
            $order->deleteShipping($shipping->shippingId);
        }
    }

    private function shippingProfileEligibility(): ShippingProfileEligibility
    {
        if ($this->container->has(ShippingProfileEligibility::class)) {
            return $this->container->get(ShippingProfileEligibility::class);
        }

        return new ShippingProfileEligibility(
            new ProfileMustBeOnline,
            new ProfileMustSupportShippingCountry,
        );
    }
}
