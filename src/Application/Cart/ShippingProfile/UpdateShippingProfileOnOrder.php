<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Cart\ShippingProfile;

use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Common\Taxes\TaxRate;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Order\OrderRepository;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\Shipping;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingCost;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfile;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfileId;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfileRepository;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfileState;
use Thinktomorrow\Trader\TraderConfig;

class UpdateShippingProfileOnOrder
{
    private TraderConfig $config;
    private OrderRepository $orderRepository;
    private ShippingProfileRepository $shippingProfileRepository;

    public function __construct(TraderConfig $config, OrderRepository $orderRepository, ShippingProfileRepository $shippingProfileRepository)
    {
        $this->config = $config;
        $this->orderRepository = $orderRepository;
        $this->shippingProfileRepository = $shippingProfileRepository;
    }

    public function handle(Order $order, ShippingProfileId $shippingProfileId): void
    {
        $shippingProfile = $this->shippingProfileRepository->find($shippingProfileId);

        if (! in_array($shippingProfile->getState(), ShippingProfileState::onlineStates())) {
            $this->removeShippingProfileFromOrder($order, $shippingProfile);

            return;
        }

//        // Country of shipment is needed when shipping profile requires address.
//        if (! $shippingCountryId = $order->getShippingAddress()?->getAddress()->countryId) {
//            throw new CouldNotSelectShippingProfileDueToMissingShippingCountry(
//                'Order [' . $order->orderId->get() . '] missing a shipping country that is required when selecting a shipping profile ' . $shippingProfile->shippingProfileId->get()
//            );
//        }

        if ($shippingProfile->requiresAddress()) {
            // Country of shipment is needed when shipping profile requires address.
            if (! $shippingCountryId = $order->getShippingAddress()?->getAddress()->countryId) {
                $this->removeShippingProfileFromOrder($order, $shippingProfile);

                return;
            }

            if (! $shippingProfile->hasCountry($shippingCountryId)) {
                $this->removeShippingProfileFromOrder($order, $shippingProfile);

                return;
            }
        }

        // Apply matching tariff - if no tariff is found, no rate will be applied
        $tariff = $shippingProfile->findTariffByPrice($order->getSubtotal(), $this->config->doesPriceInputIncludesVat());

        $shippingCost = ShippingCost::fromMoney(
            $tariff ? $tariff->getRate() : Cash::zero(),
            TaxRate::fromString($this->config->getDefaultTaxRate()),
            $this->config->doesPriceInputIncludesVat()
        );

        if (count($order->getShippings()) > 0) {
            /** @var Shipping $existingShipping */
            $existingShipping = $order->getShippings()[0];
            $existingShipping->updateShippingProfile($shippingProfile->shippingProfileId);
            $existingShipping->updateCost($shippingCost);
            $existingShipping->addData($shippingProfile->getData());

            $order->updateShipping($existingShipping);
        } else {
            $shipping = Shipping::create(
                $order->orderId,
                $this->orderRepository->nextShippingReference(),
                $shippingProfile->shippingProfileId,
                $shippingCost
            );

            $shipping->addData($shippingProfile->getData());

            $order->addShipping($shipping);
        }
    }

    private function removeShippingProfileFromOrder(Order $order, ShippingProfile $shippingProfile)
    {
        foreach ($order->getShippings() as $shipping) {
            if ($shipping->getShippingProfileId()->equals($shippingProfile->shippingProfileId)) {
                $order->deleteShipping($shipping->shippingId);
            }
        }
    }
}
