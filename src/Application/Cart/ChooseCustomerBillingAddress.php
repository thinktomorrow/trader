<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Cart;

use Thinktomorrow\Trader\Domain\Model\Order\Address\BillingAddress;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;

class ChooseCustomerBillingAddress
{
    private string $orderId;
    private string $country;
    private string $line1;
    private string $line2;
    private string $postalCode;
    private string $city;

    public function __construct(string $orderId, string $country, string $line1, string $line2, string $postalCode, string $city)
    {
        $this->orderId = $orderId;
        $this->country = $country;
        $this->line1 = $line1;
        $this->line2 = $line2;
        $this->postalCode = $postalCode;
        $this->city = $city;
    }

    public function getOrderId(): OrderId
    {
        return OrderId::fromString($this->orderId);
    }

    public function getBillingAddress(): BillingAddress
    {
        return BillingAddress::fromArray([
            'country' => $this->country,
            'line1' => $this->line1,
            'line2' => $this->line2,
            'postalCode' => $this->postalCode,
            'city' => $this->city,
        ]);
    }
}
