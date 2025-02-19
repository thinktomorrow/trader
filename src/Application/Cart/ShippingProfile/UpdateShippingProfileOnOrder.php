<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Cart\ShippingProfile;

use Psr\Container\ContainerInterface;
use Thinktomorrow\Trader\Application\VatRate\FindVatRateForOrder;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Order\OrderRepository;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\Shipping;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingCost;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingState;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfileId;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfileRepository;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfileState;
use Thinktomorrow\Trader\TraderConfig;

class UpdateShippingProfileOnOrder
{
    private ContainerInterface $container;
    private TraderConfig $config;
    private OrderRepository $orderRepository;
    private ShippingProfileRepository $shippingProfileRepository;
    private FindVatRateForOrder $findVatRateForOrder;

    public function __construct(ContainerInterface $container, TraderConfig $config, OrderRepository $orderRepository, ShippingProfileRepository $shippingProfileRepository, FindVatRateForOrder $findVatRateForOrder)
    {
        $this->container = $container;
        $this->config = $config;
        $this->orderRepository = $orderRepository;
        $this->shippingProfileRepository = $shippingProfileRepository;
        $this->findVatRateForOrder = $findVatRateForOrder;
    }

    public function handle(Order $order, ShippingProfileId $shippingProfileId): void
    {
        $shippingProfile = $this->shippingProfileRepository->find($shippingProfileId);

        if (! in_array($shippingProfile->getState(), ShippingProfileState::onlineStates())) {
            $this->removeAllShippingsFromOrder($order);

            return;
        }

        // When shipping country is not given, but profile is country restricted, we bail out.
        if (! ($shippingCountryId = $order->getShippingAddress()?->getAddress()->countryId) && $shippingProfile->hasAnyCountries()) {
            $this->removeAllShippingsFromOrder($order);

            return;
        } // If shipping country does not match the allowed countries, we bail out.
        elseif ($shippingCountryId && ! $shippingProfile->hasCountry($shippingCountryId)) {
            $this->removeAllShippingsFromOrder($order);

            return;
        }

        // Apply matching tariff - if no tariff is found, no rate will be applied
        $tariff = $shippingProfile->findTariffByPrice($order->getSubtotal(), $this->config->doesTariffInputIncludesVat());

        $shippingCost = ShippingCost::fromMoney(
            $tariff ? $tariff->getRate() : Cash::zero(),
            $this->findVatRateForOrder->findForShippingCost($order),
            $this->config->doesTariffInputIncludesVat()
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

    private function removeAllShippingsFromOrder(Order $order)
    {
        foreach ($order->getShippings() as $shipping) {
            $order->deleteShipping($shipping->shippingId);
        }
    }
}
