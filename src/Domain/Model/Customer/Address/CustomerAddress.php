<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Customer\Address;

use Thinktomorrow\Trader\Domain\Common\Address\Address;
use Thinktomorrow\Trader\Domain\Common\Entity\HasData;
use Thinktomorrow\Trader\Domain\Model\Country\CountryId;
use Thinktomorrow\Trader\Domain\Model\Customer\CustomerId;

abstract class CustomerAddress
{
    use HasData;

    public readonly CustomerId $customerId;
    protected Address $address;

    private function __construct()
    {
    }

    public static function create(CustomerId $customerId, Address $address, array $data): static
    {
        $customerAddress = new static();

        $customerAddress->customerId = $customerId;
        $customerAddress->address = $address;
        $customerAddress->data = $data;

        return $customerAddress;
    }

    public static function fromMappedData(array $state, array $aggregateState): static
    {
        $address = new static();

        $address->customerId = CustomerId::fromString($aggregateState['customer_id']);
        $address->address = new Address(CountryId::fromString($state['country_id']), $state['line_1'], $state['line_2'], $state['postal_code'], $state['city']);
        $address->data = json_decode($state['data'], true);

        return $address;
    }

    public function getMappedData(): array
    {
        return [
            'customer_id' => $this->customerId->get(),
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

    public function replaceAddress(Address $address): void
    {
        $this->address = $address;
    }
}
