<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Cart;

use Thinktomorrow\Trader\Domain\Model\Country\CountryId;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;

final class ChooseShippingCountry
{
    private string $orderId;
    private string $countryId;

    public function __construct(string $orderId, string $countryId)
    {
        $this->orderId = $orderId;
        $this->countryId = $countryId;
    }

    public function getOrderId(): OrderId
    {
        return OrderId::fromString($this->orderId);
    }

    public function getCountryId(): CountryId
    {
        return CountryId::fromString($this->countryId);
    }
}
