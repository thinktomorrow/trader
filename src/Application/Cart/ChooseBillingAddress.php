<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Cart;

use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Payment\BillingAddress;

class ChooseBillingAddress
{
    private string $orderId;
    private string $country;
    private string $street;
    private string $number;
    private string $bus;
    private string $zipcode;
    private string $city;

    public function __construct(string $orderId, string $country, string $street, string $number, string $bus, string $zipcode, string $city)
    {
        $this->orderId = $orderId;
        $this->country = $country;
        $this->street = $street;
        $this->number = $number;
        $this->bus = $bus;
        $this->zipcode = $zipcode;
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
            'street'  => $this->street,
            'number'  => $this->number,
            'bus'     => $this->bus,
            'zipcode' => $this->zipcode,
            'city'    => $this->city,
        ]);
    }
}
