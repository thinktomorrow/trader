<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\RefreshCart\Adjusters;

use Thinktomorrow\Trader\Domain\Common\Context;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Application\RefreshCart\Adjuster;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingCountry;

final class AdjustShipping implements Adjuster
{
    private FindSuitableShippingProfile $shippingRepository;

    public function __construct(FindSuitableShippingProfile $shippingRepository)
    {
        $this->shippingRepository = $shippingRepository;
    }

    public function adjust(Order $order, Context $context): void
    {
        dd('Adjustshipping: ', $order);
        // Repo to check if shipping for order already exists...
        // else create it

        // Check if shippingType (with rule) is still applicable - else remove shipping?
        // check if cost is still ok or should be adjusted (due to discounts, customer perks or anything)

        // save changes to shipping

        // If shippingId is no longer the same, update order as well

        if(!$order->getShippingId()) return;

        $shippingCountry = ShippingCountry::fromString(
            $orderDetails->getShippingAddress()->getCountry() ?? $context->getDefaultShippingCountry()
        );

        $shipping = $this->shippingRepository->findMatch(
            $order->getShippingId(),
            $orderDetails->getSubTotal(),
            $shippingCountry,
            $context->getDate(),
        );

        $order->updateShipping($shipping);
    }
}