<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Cart;

use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfileId;

final class ChooseShippingProfile
{
    private string $orderId;
    private string $shippingProfileId;

    public function __construct(string $orderId, string $shippingProfileId)
    {
        $this->orderId = $orderId;
        $this->shippingProfileId = $shippingProfileId;
    }

    public function getOrderId(): OrderId
    {
        return OrderId::fromString($this->orderId);
    }

    public function getShippingProfileId(): ShippingProfileId
    {
        return ShippingProfileId::fromString($this->shippingProfileId);
    }
}
