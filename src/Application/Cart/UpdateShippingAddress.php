<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Cart;

use Thinktomorrow\Trader\Domain\Common\Address\Address;
use Thinktomorrow\Trader\Domain\Model\Country\CountryId;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;

class UpdateShippingAddress
{
    private string $orderId;
    private ?string $countryId;
    private ?string $line1;
    private ?string $line2;
    private ?string $postalCode;
    private ?string $city;

    public function __construct(string $orderId, ?string $countryId = null, ?string $line1 = null, ?string $line2 = null, ?string $postalCode = null, ?string $city = null)
    {
        $this->orderId = $orderId;
        $this->countryId = $countryId;
        $this->line1 = $line1;
        $this->line2 = $line2;
        $this->postalCode = $postalCode;
        $this->city = $city;
    }

    public function getOrderId(): OrderId
    {
        return OrderId::fromString($this->orderId);
    }

    public function getAddress(): Address
    {
        return new Address(
            CountryId::fromString($this->countryId),
            $this->line1,
            $this->line2,
            $this->postalCode,
            $this->city,
        );
    }
}
