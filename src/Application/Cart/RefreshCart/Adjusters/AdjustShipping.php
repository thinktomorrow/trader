<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjusters;

use Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjuster;
use Thinktomorrow\Trader\Application\Cart\ShippingProfile\UpdateShippingProfileOnOrder;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfile;

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

        $this->updateShippingProfileOnOrder->refresh($order, $shippingProfileId);
    }

    /**
     * Load the selected profile once for reuse across promo combination trials.
     * This profile can then be used by the method adjustWithProfile
     */
    public function selectedProfileFor(Order $order): ?ShippingProfile
    {
        $shipping = $order->getShippings()[0] ?? null;

        return $shipping === null
            ? null
            : $this->updateShippingProfileOnOrder->findForRefresh($shipping->getShippingProfileId());
    }

    /**
     * Refresh shipping on this order using the preloaded profile, without another lookup.
     * This is used by promo evaluation to reduce memory overload on n+1 lookups
     */
    public function adjustWithProfile(Order $order, ?ShippingProfile $profile): void
    {
        if ($order->getShippings() === []) {
            return;
        }

        $this->updateShippingProfileOnOrder->refreshWithProfile($order, $profile);
    }
}
