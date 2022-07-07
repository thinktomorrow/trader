<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjusters;

use Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjuster;
use Thinktomorrow\Trader\Application\Cart\ShippingProfile\UpdateShippingProfileOnOrder;
use Thinktomorrow\Trader\Domain\Model\Order\Order;

final class AdjustShipping implements Adjuster
{
    private UpdateShippingProfileOnOrder $updateShippingProfileOnOrder;

    public function __construct(UpdateShippingProfileOnOrder $updateShippingProfileOnOrder)
    {
        $this->updateShippingProfileOnOrder = $updateShippingProfileOnOrder;
    }

    public function adjust(Order $order): void
    {
        // No shipping profile selected yet...
        if (count($order->getShippings()) < 1) {
            return;
        }

        $shippingProfileId = $order->getShippings()[0]->getShippingProfileId();

        $this->updateShippingProfileOnOrder->handle($order, $shippingProfileId);
    }
}
