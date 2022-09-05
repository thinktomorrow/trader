<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\Address;

use Thinktomorrow\Trader\Domain\Common\Address\Address;
use Thinktomorrow\Trader\Domain\Common\Entity\HasData;
use Thinktomorrow\Trader\Domain\Model\Country\CountryId;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;

abstract class OrderAddress
{
    use HasData;

    public readonly OrderId $orderId;
    protected Address $address;

    private function __construct()
    {
    }

    public static function create(OrderId $orderId, Address $address, array $data): static
    {
        $orderAddress = new static();

        $orderAddress->orderId = $orderId;
        $orderAddress->address = $address;
        $orderAddress->data = $data;

        return $orderAddress;
    }

    public static function fromMappedData(array $state, array $aggregateState): static
    {
        $address = new static();

        $address->orderId = OrderId::fromString($aggregateState['order_id']);
        $address->address = new Address(
            CountryId::fromString($state['country_id']),
            $state['line_1'],
            $state['line_2'],
            $state['postal_code'],
            $state['city']
        );
        $address->data = json_decode($state['data'], true);

        return $address;
    }

    public function getMappedData(): array
    {
        return [
            'order_id' => $this->orderId->get(),
            'country_id' => $this->address->countryId->get(),
            'line_1' => $this->address->line1,
            'line_2' => $this->address->line2,
            'postal_code' => $this->address->postalCode,
            'city' => $this->address->city,
            'data' => json_encode($this->data),
        ];
    }

    public function getAddress(): Address
    {
        return $this->address;
    }
}
