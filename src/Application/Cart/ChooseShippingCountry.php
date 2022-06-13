<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Cart;

use Thinktomorrow\Trader\Domain\Model\Order\Address\ShippingCountry;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;

final class ChooseShippingCountry
{
    private string $orderId;
    private string $country;

    public function __construct(string $orderId, string $country)
    {
        $this->orderId = $orderId;
        $this->country = $country;
    }

    public function getOrderId(): OrderId
    {
        return OrderId::fromString($this->orderId);
    }

    public function getShippingCountry(): ShippingCountry
    {
        return ShippingCountry::fromString($this->country);
    }
}
