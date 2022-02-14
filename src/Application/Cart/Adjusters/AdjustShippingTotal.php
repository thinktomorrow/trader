<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Cart\Adjusters;

use Thinktomorrow\Trader\Domain\Common\Context;
use Thinktomorrow\Trader\Domain\Model\Order\Entity\Order;
use Thinktomorrow\Trader\Domain\Model\Shipping\ShippingCountry;
use Thinktomorrow\Trader\Domain\Model\Order\Details\OrderDetails;

final class AdjustShippingTotal implements Adjuster
{
    private MatchingShippingRepository $shippingRepository;

    public function __construct(MatchingShippingRepository $shippingRepository)
    {
        $this->shippingRepository = $shippingRepository;
    }

    public function adjust(Order $order, OrderDetails $orderDetails, Context $context): void
    {
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
